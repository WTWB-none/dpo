<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
$address = $input['address'] ?? '';

if (empty($address)) {
    http_response_code(400);
    echo json_encode(['error' => 'Address is required']);
    exit;
}

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

try {
    $geocodeUrl = 'https://geocode-maps.yandex.ru/1.x/?apikey=' . YANDEX_API_KEY
        . '&geocode=' . urlencode($address)
        . '&format=json'
        . '&results=1';

    $geocodeData = makeRequest($geocodeUrl);

    $featureMember = $geocodeData['response']['GeoObjectCollection']['featureMember'][0] ?? null;
    if (!$featureMember) {
        http_response_code(404);
        echo json_encode(['error' => 'Address not found']);
        exit;
    }

    $geoObject = $featureMember['GeoObject'];
    $point = explode(' ', $geoObject['Point']['pos']); // [долгота, широта]
    $structuredAddress = $geoObject['metaDataProperty']['GeocoderMetaData']['text'];
    $components = $geoObject['metaDataProperty']['GeocoderMetaData']['Address']['Components'];

    $metroUrl = 'https://geocode-maps.yandex.ru/1.x/?apikey=' . YANDEX_API_KEY
        . '&geocode=' . $point[0] . ',' . $point[1]
        . '&kind=metro'
        . '&format=json'
        . '&results=1';

    $metroData = makeRequest($metroUrl);
    $metro = $metroData['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['name'] ?? 'Метро не найдено';

    $addressComponents = [];
    foreach ($components as $comp) {
        $addressComponents[$comp['kind']] = $comp['name'];
    }

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

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}