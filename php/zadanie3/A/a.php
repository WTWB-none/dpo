<?php
function calculateFlightTime($input)
{
    $lines = explode("\n", trim($input));
    $n = (int) $lines[0];

    $results = [];
    for ($i = 0; $i < $n; $i++) {
        $line = trim($lines[$i + 1]);
        list($departureTime, $departureTimezone, $arrivalTime, $arrivalTimezone) = explode(' ', $line);

        $departureDt = DateTime::createFromFormat('d.m.Y_H:i:s', $departureTime);
        $departureUtc = clone $departureDt;
        $departureUtc->modify((-1 * (int) $departureTimezone) . " hours");

        $arrivalDt = DateTime::createFromFormat('d.m.Y_H:i:s', $arrivalTime);
        $arrivalUtc = clone $arrivalDt;
        $arrivalUtc->modify((-1 * (int) $arrivalTimezone) . " hours");

        $flightDuration = $arrivalUtc->getTimestamp() - $departureUtc->getTimestamp();

        $results[] = $flightDuration;
    }

    return implode("\n", $results);
}

$datDir = "./test";
$ansDir = "./test";

if (!is_dir($datDir)) {
    echo "Error: Test data directory '$datDir' not found\n";
    exit(1);
}

if (!is_dir($ansDir)) {
    echo "Error: Answer directory '$ansDir' not found\n";
    exit(1);
}

$datFiles = glob("$datDir/*.dat");

if (empty($datFiles)) {
    echo "No .dat files found in '$datDir'\n";
    exit(1);
}

echo "Running tests on " . count($datFiles) . " files...\n";
$allTestsPassed = true;

foreach ($datFiles as $datFile) {
    $testNum = basename($datFile, '.dat');
    $ansFile = "$ansDir/$testNum.ans";

    if (!file_exists($ansFile)) {
        echo "Warning: Answer file for test $testNum not found\n";
        continue;
    }

    $input = file_get_contents($datFile);
    $expectedOutput = trim(file_get_contents($ansFile));

    $actualOutput = calculateFlightTime($input);
    echo $actualOutput;

    if ($actualOutput === $expectedOutput) {
        echo "Test $testNum: PASSED\n";
    } else {
        echo "Test $testNum: FAILED\n";
        echo "Expected: $expectedOutput\n";
        echo "Got:      $actualOutput\n";
        $allTestsPassed = false;
    }
}

if ($allTestsPassed) {
    echo "All tests passed!\n";
} else {
    echo "Some tests failed. Please check the output above.\n";
}

if (count($argv) > 1 && $argv[1] === 'run') {
    $input = file_exists('input.txt') ? file_get_contents('input.txt') : stream_get_contents(STDIN);
    echo calculateFlightTime($input);
}
?>