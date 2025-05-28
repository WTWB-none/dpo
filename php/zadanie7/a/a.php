<?php
$input = "input.txt";
$output = "output.txt";

$lines = file($input, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$stats = [];

foreach ($lines as $line) {
	// разделим строку на части: ID и вся остальная строка — дата и время
	$parts = preg_split('/\s+/', $line, 2);
	if (count($parts) < 2) continue;

	[$id, $datetime] = $parts;

	if (!isset($stats[$id])) {
		$stats[$id] = ['count' => 0, 'last' => $datetime];
	}

	$stats[$id]['count']++;

	if (strtotime($datetime) > strtotime($stats[$id]['last'])) {
		$stats[$id]['last'] = $datetime;
	}
}

$fp = fopen($output, 'w');
foreach ($stats as $id => $data) {
	fwrite($fp, "{$data['count']} $id {$data['last']}\n");
}
fclose($fp);
