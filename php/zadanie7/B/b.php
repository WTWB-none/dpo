<?php
$lines = file("input.txt", FILE_IGNORE_NEW_LINES);
$sections = [];

foreach ($lines as $line) {
	list($id, $name, $l, $r) = explode(" ", $line);
	$sections[] = ['id' => $id, 'name' => $name, 'left' => $l, 'right' => $r];
}

usort($sections, fn($a, $b) => $a['left'] - $b['left']);

$stack = [];
$output = fopen("output.txt", "w");
foreach ($sections as $section) {
	while (!empty($stack) && end($stack)['right'] < $section['right']) {
		array_pop($stack);
	}
	fwrite($output, str_repeat("-", count($stack)) . $section['name'] . "\n");
	$stack[] = $section;
}
fclose($output);
