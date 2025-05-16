<?php
// Set HTTP headers for JSON response and CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Load configuration (e.g., YANDEX_API_KEY)
require_once 'config.php';

// Parse JSON input from request body
$input = json_decode(file_get_contents('php://input'), true);
$address = $input['address'] ?? '';

// Validate address input
if (empty($address)) {
    http_response_code(400);
    echo json_encode(['error' => 'Address is required']);
    exit;
}

/**
 * Makes an HTTP GET request to a specified URL and returns the JSON response.
 *
 * Uses cURL to fetch data from an external API, with SSL verification disabled
 * for flexibility. Throws an exception on cURL errors.
 *
 * @param string $url The URL to send the GET request to
 * @return array Decoded JSON response as an associative array
 * @throws Exception If a cURL error occurs
 */
function makeRequest($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception('cURL error: ' . curl_error($ch));
    }
    curl_close($ch);
    return json_decode($response, true);
}

/**
 * Processes geocoding and metro lookup for a given address.
 *
 * Queries Yandex Geocode API to retrieve coordinates, structured address, and
 * nearest metro station. Returns results in JSON format or error responses on failure.
 */
try {
    // Construct geocoding API URL
    $geocodeUrl = 'https://geocode-maps.yandex.ru/1.x/?apikey=' . YANDEX_API_KEY
        . '&geocode=' . urlencode($address)
        . '&format=json'
        . '&results=1';

    // Fetch geocoding data
    $geocodeData = makeRequest($geocodeUrl);

    // Validate geocoding response
    $featureMember = $geocodeData['response']['GeoObjectCollection']['featureMember'][0] ?? null;
    if (!$featureMember) {
        http_response_code(404);
        echo json_encode(['error' => 'Address not found']);
        exit;
    }

    // Extract geocoding details
    $geoObject = $featureMember['GeoObject'];
    $point = explode(' ', $geoObject['Point']['pos']); // [longitude, latitude]
    $structuredAddress = $geoObject['metaDataProperty']['GeocoderMetaData']['text'];
    $components = $geoObject['metaDataProperty']['GeocoderMetaData']['Address']['Components'];

    // Construct metro lookup API URL
    $metroUrl = 'https://geocode-maps.yandex.ru/1.x/?apikey=' . YANDEX_API_KEY
        . '&geocode=' . $point[0] . ',' . $point[1]
        . '&kind=metro'
        . '&format=json'
        . '&results=1';

    // Fetch nearest metro station
    $metroData = makeRequest($metroUrl);
    $metro = $metroData['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['name'] ?? 'Метро не найдено';

    // Build address components array
    $addressComponents = [];
    foreach ($components as $comp) {
        $addressComponents[$comp['kind']] = $comp['name'];
    }

    // Prepare response
    $result = [
        'structuredAddress' => [
            'fullAddress' => $structuredAddress,
            'components' => $addressComponents,
        ],
        'coordinates' => [
            'longitude' => (float) $point[0],
            'latitude' => (float) $point[1],
        ],
        'nearestMetro' => $metro,
    ];

    // Output JSON response
    echo json_encode($result);
} catch (Exception $e) {
    // Handle errors and return JSON error response
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
