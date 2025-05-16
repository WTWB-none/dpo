<?php

/**
 * Calculates flight duration in seconds between two locations considering their timezones.
 * 
 * @param string $input Input string containing the number of test cases and flight details
 *                     in the format: number of cases, followed by lines of 
 *                     "departure_time departure_timezone arrival_time arrival_timezone"
 * @return string Flight durations in seconds, one per line, separated by newlines
 */
function calculateflighttime($input)
{
    // Split input into lines and extract number of test cases
    $lines = explode("\n", trim($input));
    $n = (int) $lines[0];
    $results = [];

    // Process each test case
    for ($i = 0; $i < $n; $i++) {
        // Parse flight details: departure time, departure timezone, arrival time, arrival timezone
        $line = trim($lines[$i + 1]);
        list($departuretime, $departuretimezone, $arrivaltime, $arrivaltimezone) = explode(' ', $line);

        // Create DateTime objects for departure and convert to UTC
        $departuredt = DateTime::createFromFormat('d.m.y_H:i:s', $departuretime);
        $departureutc = clone $departuredt;
        $departureutc->modify((-1 * (int) $departuretimezone) . " hours");

        // Create DateTime objects for arrival and convert to UTC
        $arrivaldt = DateTime::createFromFormat('d.m.y_H:i:s', $arrivaltime);
        $arrivalutc = clone $arrivaldt;
        $arrivalutc->modify((-1 * (int) $arrivaltimezone) . " hours");

        // Calculate flight duration in seconds
        $flightduration = $arrivalutc->getTimestamp() - $departureutc->getTimestamp();

        $results[] = $flightduration;
    }

    // Return durations as a newline-separated string
    return implode("\n", $results);
}

// Validate test data and answer directories
$datdir = "./test";
$ansdir = "./test";

if (!is_dir($datdir)) {
    echo "error: test data directory '$datdir' not found\n";
    exit(1);
}

if (!is_dir($ansdir)) {
    echo "error: answer directory '$ansdir' not found\n";
    exit(1);
}

// Collect all .dat files for testing
$datfiles = glob("$datdir/*.dat");

if (empty($datfiles)) {
    echo "no .dat files found in '$datdir'\n";
    exit(1);
}

/**
 * Runs test cases by comparing calculated flight times against expected outputs.
 * 
 * Iterates through .dat files, processes them using calculateflighttime, and compares
 * results with corresponding .ans files. Reports pass/fail status for each test.
 */
echo "running tests on " . count($datfiles) . " files...\n";
$alltestspassed = true;

foreach ($datfiles as $datfile) {
    $testnum = basename($datfile, '.dat');
    $ansfile = "$ansdir/$testnum.ans";

    if (!file_exists($ansfile)) {
        echo "warning: answer file for test $testnum not found\n";
        continue;
    }

    // Read input and expected output
    $input = file_get_contents($datfile);
    $expectedoutput = trim(file_get_contents($ansfile));

    // Calculate actual output
    $actualoutput = calculateflighttime($input);
    echo $actualoutput;

    // Compare results and report test status
    if ($actualoutput === $expectedoutput) {
        echo "test $testnum: passed\n";
    } else {
        echo "test $testnum: failed\n";
        echo "expected: $expectedoutput\n";
        echo "got:      $actualoutput\n";
        $alltestspassed = false;
    }
}

// Summarize test results
if ($alltestspassed) {
    echo "all tests passed!\n";
} else {
    echo "some tests failed. please check the output above.\n";
}

/**
 * Handles command-line execution mode for processing input from file or STDIN.
 * 
 * If run with 'run' argument, processes input from input.txt or STDIN and outputs
 * the calculated flight times.
 */
if (count($argv) > 1 && $argv[1] === 'run') {
    $input = file_exists('input.txt') ? file_get_contents('input.txt') : stream_get_contents(STDIN);
    echo calculateflighttime($input);
}
