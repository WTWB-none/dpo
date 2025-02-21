<?php

/**
 * Читает файл со списком новостей и преобразует ссылки старого формата в новый
 * 
 * @param string $inputFile Путь к входному файлу с новостями
 * @param string $outputFile Путь к выходному файлу для записи преобразованных новостей
 * @return void
 */
function updateNewsLinks($inputFile, $outputFile)
{
    // Проверяем существование входного файла
    if (!file_exists($inputFile)) {
        echo "Ошибка: Входной файл '$inputFile' не найден\n";
        return;
    }

    // Читаем все строки из входного файла
    $lines = file($inputFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    // Открываем выходной файл для записи
    $handle = fopen($outputFile, 'w');
    if ($handle === false) {
        echo "Ошибка: Не удалось создать выходной файл '$outputFile'\n";
        return;
    }

    // Регулярное выражение для поиска старых ссылок
    // Ищем http://asozd.duma.gov.ru/main.nsf/(Spravka)?OpenAgent&RN=<номер>&<число>
    $pattern = '/http:\/\/asozd\.duma\.gov\.ru\/main\.nsf\/\(Spravka\)\?OpenAgent&RN=([\d-]+)&\d+/i';

    $updatedCount = 0;

    // Обрабатываем каждую строку
    foreach ($lines as $line) {
        $originalText = trim($line);

        // Ищем и заменяем все вхождения старых ссылок
        $newText = preg_replace_callback(
            $pattern,
            function ($matches) {
                // $matches[1] содержит номер законопроекта
                return "http://sozd.parlament.gov.ru/bill/" . $matches[1];
            },
            $originalText,
            -1,
            $count
        );

        // Записываем преобразованную строку в файл
        fwrite($handle, $newText . "\n");

        // Увеличиваем счетчик обновленных ссылок
        $updatedCount += $count;
    }

    // Закрываем файл
    fclose($handle);

    echo "Обработка завершена. Обновлено ссылок: $updatedCount\n";
    echo "Результат сохранен в '$outputFile'\n";
}

// Запрашиваем пути к файлам у пользователя
$inputFile = readline("Введите путь к входному файлу: ");
$outputFile = readline("Введите путь к выходному файлу: ");

// Запускаем обработку
updateNewsLinks($inputFile, $outputFile);

?>