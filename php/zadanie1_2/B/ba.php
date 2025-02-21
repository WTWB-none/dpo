<?php
function expand_ipv6($ip)
{
    // Разделение на блоки
    $parts = explode(':', $ip);

    // Обработка сокращенного :: если оно есть
    $emptyCount = 8 - count($parts) + substr_count($ip, '::');
    $insert = array_fill(0, $emptyCount, '0000');
    $pos = array_search('', $parts, true);
    if ($pos !== false) {
        array_splice($parts, $pos, 1, $insert);
    } else {
        $parts = array_merge($parts, $insert);
    }

    // Заполнение нулями каждого блока до 4 символов
    $parts = array_map(function ($part) {
        return str_pad($part, 4, '0', STR_PAD_LEFT);
    }, $parts);

    return implode(':', $parts);
}

while ($line = trim(fgets(STDIN))) {
    echo expand_ipv6($line) . "\n";
}
?>