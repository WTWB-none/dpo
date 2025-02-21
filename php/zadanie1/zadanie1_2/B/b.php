<?php

/**
 * Преобразует сокращенный IPv6-адрес в полный формат
 * 
 * @param string $address Сокращенный IPv6-адрес (например, "2001:db8::1")
 * @return string Полный IPv6-адрес с восемью блоками по четыре hex-символа каждый,
 *                разделенными двоеточиями (например, "2001:0db8:0000:0000:0000:0000:0000:0001")
 */
function expandIPv6($address)
{
    // Удаляем завершающее двоеточие, если есть
    $address = rtrim($address, ':');

    // Разделяем адрес на блоки по двоеточию
    $parts = explode(':', $address);

    // Обрабатываем случай с пропущенными блоками (::)
    if (in_array('', $parts)) {
        $emptyIndex = array_search('', $parts);

        // Удаляем пустые элементы
        $parts = array_filter($parts, function ($value) {
            return $value !== '';
        });

        // Вычисляем количество недостающих блоков и заполняем нулями
        $missingBlocks = 8 - count($parts);
        $zeroBlocks = array_fill(0, $missingBlocks, '0');
        array_splice($parts, $emptyIndex, 0, $zeroBlocks);
    }

    // Дополняем каждый блок до 4 символов ведущими нулями
    $expandedParts = [];
    foreach ($parts as $part) {
        $expandedParts[] = str_pad($part, 4, '0', STR_PAD_LEFT);
    }

    // Объединяем блоки двоеточиями
    return implode(':', $expandedParts);
}

/**
 * Обрабатывает один тестовый случай, сравнивая преобразованные IPv6-адреса с ожидаемыми
 * 
 * @param string $inputFile Путь к файлу с входными данными (список IPv6-адресов)
 * @param string $outputFile Путь к файлу с ожидаемыми результатами
 * @return array Ассоциативный массив с результатом теста:
 *               'result' => строка "Тест пройден" или "Тест не пройден"
 */
function processTestCase($inputFile, $outputFile)
{
    // Читаем входной файл
    $lines = file($inputFile, FILE_IGNORE_NEW_LINES);
    $currentLine = 0;

    // Собираем все адреса из входного файла
    $addresses = [];
    for ($i = 0; $i < count($lines); $i++) {
        $line = trim($lines[$currentLine++]);
        array_push($addresses, $line);
    }

    // Преобразуем каждый адрес
    $result = [];
    foreach ($addresses as $address) {
        $res = expandIPv6($address);
        // Дополняем адрес до полной длины, если необходимо
        while (strlen($res) < 39) {
            $res = $res . ":0000";
        }
        array_push($result, $res);
    }

    // Читаем ожидаемые результаты
    $exp_adr = [];
    $exp_lines = file($outputFile, FILE_IGNORE_NEW_LINES);
    $currentLine = 0;

    for ($i = 0; $i < count($exp_lines); $i++) {
        $eline = trim($exp_lines[$currentLine++]);
        array_push($exp_adr, $eline);
    }

    // Сравниваем результаты с ожидаемыми
    $auth = ($result === $exp_adr) ? "Тест пройден" : "Тест не пройден";

    return [
        'result' => $auth
    ];
}

/**
 * Запускает все тесты из указанных директорий
 * 
 * @param string $inputDir Директория с входными файлами тестов (*.dat)
 * @param string $outputDir Директория с файлами ожидаемых результатов (*.ans)
 * @return array Массив результатов всех тестов, где каждый элемент - результат от processTestCase
 */
function runTests($inputDir, $outputDir)
{
    $results = [];
    // Получаем список всех .dat файлов
    $datFiles = glob($inputDir . "/*.dat");

    foreach ($datFiles as $inputFile) {
        $basename = basename($inputFile, '.dat');
        $outputFile = $outputDir . '/' . $basename . '.ans';

        // Обрабатываем тест, если существует соответствующий выходной файл
        if (file_exists($outputFile)) {
            $results[] = processTestCase($inputFile, $outputFile);
        }
    }

    return $results;
}

// Указываем директории с тестовыми данными
$inputDir = "./tests/input";
$outputDir = "./tests/output";

// Запускаем тесты
$testResults = runTests($inputDir, $outputDir);

$totalTests = count($testResults);
$passedTests = 0;

// Выводим результаты тестирования
echo "Результаты тестирования:\n";
echo "=====================\n";

foreach ($testResults as $result) {
    echo "Итог: " . $result['result'] . "\n";
    echo "---------------------\n";
}
