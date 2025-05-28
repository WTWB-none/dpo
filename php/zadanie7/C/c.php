<?php
$input = "input.txt";
$output = "output.txt";

$lines = file($input, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$weights = [];
$totalWeight = 0;

foreach ($lines as $line) {
	[$id, $weight] = preg_split('/\s+/', $line, 2);
	$weight = (int)$weight;
	$weights[] = ['id' => $id, 'weight' => $weight];
	$totalWeight += $weight;
}

// 1. Пропорциональное распределение
$raw_counts = [];
$totalAssigned = 0;

foreach ($weights as $w) {
	$ideal = 106 * $w['weight'] / $totalWeight;
	$count = floor($ideal);
	$raw_counts[$w['id']] = ['count' => $count, 'fraction' => $ideal - $count];
	$totalAssigned += $count;
}

// 2. Распределить оставшиеся (106 - totalAssigned) по наибольшей дробной части
$left = 106 - $totalAssigned;
uasort($raw_counts, fn($a, $b) => $b['fraction'] <=> $a['fraction']);

foreach ($raw_counts as $id => &$data) {
	if ($left <= 0) break;
	$data['count']++;
	$left--;
}
unset($data);

// 3. Вывести доли
$fp = fopen($output, 'w');
foreach ($weights as $w) {
	$cnt = $raw_counts[$w['id']]['count'];
	$ratio = $cnt / 106;
	fwrite($fp, "{$w['id']} " . number_format($ratio, 6, '.', '') . "\n");
}
fclose($fp);
