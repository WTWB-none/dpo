<?php

/**
 * Проверяет длину строки на соответствие заданному диапазону.
 * 
 * @param string $value Входная строка для проверки.
 * @param int $min Минимально допустимая длина строки.
 * @param int $max Максимально допустимая длина строки.
 * @return bool Возвращает true, если длина строки находится в диапазоне [$min, $max], иначе false.
 */
function validateString($value, $min, $max)
{
    $length = mb_strlen($value);
    return $length >= $min && $length <= $max;
}

/**
 * Проверяет, является ли значение целым числом в заданном диапазоне.
 * 
 * @param string $value Входное значение для проверки (ожидается строка, представляющая число).
 * @param int $min Минимально допустимое значение числа.
 * @param int $max Максимально допустимое значение числа.
 * @return bool Возвращает true, если значение является целым числом в диапазоне [$min, $max], иначе false.
 */
function validateNumber($value, $min, $max)
{
    if (!is_numeric($value)) {
        return false;
    }

    $num = (float) $value;
    if (floor($num) != $num) {
        return false; // Число должно быть целым
    }

    return $num >= $min && $num <= $max;
}

/**
 * Проверяет, соответствует ли строка формату российского номера телефона.
 * 
 * @param string $value Входная строка для проверки (например, "+7 (123) 456-78-90").
 * @return bool Возвращает true, если строка соответствует формату "+7 (XXX) XXX-XX-XX", иначе false.
 */
function validatePhone($value)
{
    return preg_match('/^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/', $value) === 1;
}

/**
 * Проверяет, является ли строка валидной датой и временем в формате DD.MM.YYYY HH:MM.
 * Поддерживает 1-2 цифры для дня, месяца и часов.
 * 
 * @param string $value Входная строка для проверки (например, "17.12.1964 1:38").
 * @return bool Возвращает true, если строка представляет валидную дату и время, иначе false.
 */
function validateDateTime($value)
{
    if (!preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4}) (\d{1,2}):(\d{2})$/', $value, $matches)) {
        return false;
    }

    $day = (int) $matches[1];
    $month = (int) $matches[2];
    $year = (int) $matches[3];
    $hour = (int) $matches[4];
    $minute = (int) $matches[5];

    return checkdate($month, $day, $year) &&
        $hour >= 0 && $hour <= 23 &&
        $minute >= 0 && $minute <= 59;
}

/**
 * Проверяет, является ли строка валидным email-адресом с учетом дополнительных ограничений.
 * Формат: имя@домен.tld, где имя (4-30 символов, буквы, цифры, "_", не начинается с "_"),
 * домен (2-30 букв), TLD (2-10 букв в нижнем регистре).
 * 
 * @param string $value Входная строка для проверки (например, "user123@domain.com").
 * @return bool Возвращает true, если строка соответствует заданным правилам email, иначе false.
 */
function validateEmail($value)
{
    if (!preg_match('/^([^@]+)@([^@.]+)\.([^@.]+)$/', $value, $matches)) {
        return false;
    }

    $name = $matches[1];
    $domain = $matches[2];
    $tld = $matches[3];

    if (mb_strlen($name) < 4 || mb_strlen($name) > 30) {
        return false;
    }
    if (substr($name, 0, 1) === '_') {
        return false;
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
        return false;
    }

    if (mb_strlen($domain) < 2 || mb_strlen($domain) > 30) {
        return false;
    }
    if (!preg_match('/^[a-zA-Z]+$/', $domain)) {
        return false;
    }

    if (mb_strlen($tld) < 2 || mb_strlen($tld) > 10) {
        return false;
    }
    if (!preg_match('/^[a-z]+$/', $tld)) {
        return false;
    }

    return true;
}

/**
 * Валидирует массив строк, каждая из которых содержит значение в скобках <...> и правило проверки.
 * Правила: S (строка), N (число), P (телефон), D (дата/время), E (email).
 * 
 * @param array $lines Массив строк для валидации (например, ["<abc> S 1 5", "<5> N 1 10"]).
 * @return string Результат валидации в виде строк, разделенных переносами (\n), где каждая строка — "OK" или "FAIL".
 */
function validate($lines)
{
    $results = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line))
            continue;

        if (!preg_match('/<([^>]*)> (.+)/', $line, $matches)) {
            $results[] = 'FAIL';
            continue;
        }

        $value = $matches[1];
        $validationInfo = $matches[2];
        $parts = explode(' ', $validationInfo);
        $rule = $parts[0];

        switch ($rule) {
            case 'S':
                $min = (int) $parts[1];
                $max = (int) $parts[2];
                $results[] = validateString($value, $min, $max) ? 'OK' : 'FAIL';
                break;

            case 'N':
                $min = (int) $parts[1];
                $max = (int) $parts[2];
                $results[] = validateNumber($value, $min, $max) ? 'OK' : 'FAIL';
                break;

            case 'P':
                $results[] = validatePhone($value) ? 'OK' : 'FAIL';
                break;

            case 'D':
                $results[] = validateDateTime($value) ? 'OK' : 'FAIL';
                break;

            case 'E':
                $results[] = validateEmail($value) ? 'OK' : 'FAIL';
                break;

            default:
                $results[] = 'FAIL';
        }
    }

    return implode("\n", $results);
}

/**
 * Обрабатывает один тестовый случай, сравнивая результат валидации с ожидаемым из файла.
 * 
 * @param string $inputFile Путь к входному файлу с тестовыми данными (.dat).
 * @param string $outputFile Путь к файлу с ожидаемым результатом (.ans).
 * @return array Ассоциативный массив с информацией о тесте: путь к файлу, вычисленный и ожидаемый результаты.
 */
function processTestCase($inputFile, $outputFile)
{
    $lines = file($inputFile, FILE_IGNORE_NEW_LINES);
    $result = validate($lines);
    $expected = trim(file_get_contents($outputFile));

    return [
        'input_file' => $inputFile,
        'calculated' => $result,
        'expected' => $expected,
    ];
}

/**
 * Запускает тесты для всех файлов в указанных директориях.
 * 
 * @param string $inputDir Директория с входными файлами (.dat).
 * @param string $outputDir Директория с ожидаемыми результатами (.ans).
 * @return array Массив результатов обработки тестовых случаев.
 */
function runTests($inputDir, $outputDir)
{
    $results = [];
    $datFiles = glob($inputDir . "/*.dat");

    foreach ($datFiles as $inputFile) {
        $basename = basename($inputFile, '.dat');
        $outputFile = $outputDir . '/' . $basename . '.ans';

        if (file_exists($outputFile)) {
            $results[] = processTestCase($inputFile, $outputFile);
        }
    }

    return $results;
}

/**
 * Основной блок кода: настройка путей и запуск тестов при указании аргумента --test.
 */
$inputDir = "./test/input";
$outputDir = "./test/output";

if (isset($argv) && count($argv) > 1 && $argv[1] === '--test') {
    $testResults = runTests($inputDir, $outputDir);

    echo "Результаты тестирования:\n";
    echo "=====================\n";

    foreach ($testResults as $result) {
        echo "Тест {$result['input_file']}:\n";
        echo "  Ожидаемый результат: {$result['expected']}\n";
        echo "  Полученный результат: {$result['calculated']}\n";
        echo "Итог: " . ($result['expected'] == $result['calculated'] ? "Тест пройден" : "Тест не пройден") . "\n";
        echo "---------------------\n";
    }
}
