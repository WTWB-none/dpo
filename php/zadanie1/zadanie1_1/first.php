<?php
$a = readline("Введите строку: ");

$a = preg_replace_callback("/'([0-9]+)'/", function ($v) {
    return "'" . ($v[1] * 2) . "'";
}, $a);

echo "$a\n";
?>