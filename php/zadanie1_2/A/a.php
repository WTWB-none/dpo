<?php

/**
 * Рассчитывает итоговый баланс на основе ставок и результатов игр
 * 
 * @param array $bets Массив ставок, где каждая ставка - [gameId, amount, prediction]
 *                    gameId (string) - идентификатор игры
 *                    amount (int) - сумма ставки
 *                    prediction (string) - прогноз ('L', 'R' или 'D')
 * @param array $games Массив игр, где каждая игра - [id, leftCoef, rightCoef, drawCoef, result]
 *                     id (string) - идентификатор игры
 *                     leftCoef (float) - коэффициент на левую команду
 *                     rightCoef (float) - коэффициент на правую команду
 *                     drawCoef (float) - коэффициент на ничью
 *                     result (string) - результат игры ('L', 'R' или 'D')
 * @return int Итоговый баланс после учета всех ставок
 */
function calculateBalance($bets, $games)
{
    $balance = 0;

    // Создаем ассоциативный массив игр для быстрого доступа по ID
    $gamesMap = [];
    foreach ($games as $game) {
        list($id, $leftCoef, $rightCoef, $drawCoef, $result) = $game;
        $gamesMap[$id] = [
            'leftCoef' => (float) $leftCoef,
            'rightCoef' => (float) $rightCoef,
            'drawCoef' => (float) $drawCoef,
            'result' => $result
        ];
    }

    // Обрабатываем каждую ставку
    foreach ($bets as $bet) {
        list($gameId, $amount, $prediction) = $bet;

        // Пропускаем ставку, если игра не найдена
        if (!isset($gamesMap[$gameId])) {
            continue;
        }

        $game = $gamesMap[$gameId];
        $result = $game['result'];

        // Если предсказание верно, добавляем выигрыш минус ставка
        if ($prediction === $result) {
            switch ($prediction) {
                case 'L':
                    $balance += $amount * $game['leftCoef'] - $amount;
                    break;
                case 'R':
                    $balance += $amount * $game['rightCoef'] - $amount;
                    break;
                case 'D':
                    $balance += $amount * $game['drawCoef'] - $amount;
                    break;
            }
        } else {
            // Если предсказание неверно, вычитаем сумму ставки
            $balance -= $amount;
        }
    }

    return $balance;
}

/**
 * Обрабатывает один тестовый случай, сравнивая вычисленный результат с ожидаемым
 * 
 * @param string $inputFile Путь к входному файлу с данными теста
 * @param string $outputFile Путь к файлу с ожидаемым результатом
 * @return array Ассоциативный массив с результатами теста:
 *               'input_file' => путь к входному файлу
 *               'calculated' => вычисленный результат
 *               'expected' => ожидаемый результат
 */
function processTestCase($inputFile, $outputFile)
{
    $lines = file($inputFile, FILE_IGNORE_NEW_LINES);
    $currentLine = 0;

    // Читаем количество ставок
    $n = (int) $lines[$currentLine++];

    // Читаем ставки
    $bets = [];
    for ($i = 0; $i < $n; $i++) {
        $line = trim($lines[$currentLine++]);
        list($gameId, $amount, $prediction) = explode(' ', $line);
        $bets[] = [$gameId, (int) $amount, $prediction];
    }

    // Читаем количество игр
    $m = (int) $lines[$currentLine++];

    // Читаем данные игр
    $games = [];
    for ($i = 0; $i < $m; $i++) {
        $line = trim($lines[$currentLine++]);
        list($id, $leftCoef, $rightCoef, $drawCoef, $result) = explode(' ', $line);
        $games[] = [$id, $leftCoef, $rightCoef, $drawCoef, $result];
    }

    // Вычисляем баланс
    $result = calculateBalance($bets, $games);

    // Читаем ожидаемый результат
    $expected = (int) trim(file_get_contents($outputFile));

    return [
        'input_file' => $inputFile,
        'calculated' => $result,
        'expected' => $expected,
    ];
}

/**
 * Запускает все тесты из указанных директорий
 * 
 * @param string $inputDir Директория с входными файлами тестов (*.dat)
 * @param string $outputDir Директория с файлами ожидаемых результатов (*.ans)
 * @return array Массив результатов всех тестов
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

// Указываем директории с тестовыми данными
$inputDir = "./tests/input";
$outputDir = "./tests/output";

// Запускаем тесты
$testResults = runTests($inputDir, $outputDir);

$totalTests = count($testResults);
$passedTests = 0;

// Выводим результаты
echo "Результаты тестирования:\n";
echo "=====================\n";

foreach ($testResults as $result) {
    echo "Тест {$result['input_file']}:\n";
    echo "  Ожидаемый результат: {$result['expected']}\n";
    echo "  Полученный результат: {$result['calculated']}\n";
    echo "Итог: " . ($result['expected'] == $result['calculated'] ? "Тест пройден" : "Тест не пройден") . "\n";
    echo "---------------------\n";
}
