<?php

/**
 * Merges sections and products XML files into a single catalog XML structure.
 *
 * Combines sections and their associated products based on section IDs, producing
 * a formatted XML output with sections and their corresponding products.
 *
 * @param string $sectionsXmlPath Path to the sections XML file
 * @param string $productsXmlPath Path to the products XML file
 * @return string Formatted XML string containing merged catalog data
 */
function mergeCatalog($sectionsXmlPath, $productsXmlPath)
{
    $sectionsXml = simplexml_load_file($sectionsXmlPath);
    $productsXml = simplexml_load_file($productsXmlPath);

    // Initialize output XML structure
    $outputXml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><ЭлементыКаталога><Разделы></Разделы></ЭлементыКаталога>');

    // Iterate through each section
    foreach ($sectionsXml->Раздел as $section) {
        $sectionId = (string) $section->Ид;

        // Add section to output XML
        $outputSection = $outputXml->Разделы->addChild('Раздел');
        $outputSection->addChild('Ид', $sectionId);
        $outputSection->addChild('Наименование', (string) $section->Наименование);

        $outputProducts = $outputSection->addChild('Товары');

        // Check and process products for the current section
        if (isset($productsXml->Товар)) {
            foreach ($productsXml->Товар as $product) {
                $belongsToSection = false;
                if (isset($product->Разделы->ИдРаздела)) {
                    foreach ($product->Разделы->ИдРаздела as $productSectionId) {
                        if ((string) $productSectionId === $sectionId) {
                            $belongsToSection = true;
                            break;
                        }
                    }
                }

                // Add matching products to the section
                if ($belongsToSection) {
                    $outputProduct = $outputProducts->addChild('Товар');
                    $outputProduct->addChild('Ид', (string) $product->Ид);
                    $outputProduct->addChild('Наименование', (string) $product->Наименование);
                    $outputProduct->addChild('Артикул', (string) $product->Артикул);
                }
            }
        }
    }

    // Format XML output using DOMDocument
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($outputXml->asXML());

    return $dom->saveXML();
}

/**
 * Executes test cases to validate the mergeCatalog function.
 *
 * Scans the test directory for section and product XML files, runs mergeCatalog,
 * and compares results against expected outputs. Reports test results and saves
 * failing outputs for inspection.
 *
 * @return bool True if all tests pass, false otherwise
 */
function runTests()
{
    $testDir = './test';

    // Validate test directory
    if (!is_dir($testDir)) {
        echo "Error: Test directory '$testDir' not found\n";
        return false;
    }

    // Collect section XML files
    $sectionFiles = glob("$testDir/*_sections.xml");

    if (empty($sectionFiles)) {
        echo "No test files found in '$testDir'\n";
        return false;
    }

    // Extract test case numbers
    $testCases = [];
    foreach ($sectionFiles as $file) {
        preg_match('/(\d+)_sections\.xml$/', $file, $matches);
        if (isset($matches[1])) {
            $testCases[] = $matches[1];
        }
    }

    sort($testCases);
    echo "Found " . count($testCases) . " test cases: " . implode(', ', $testCases) . "\n";

    $allTestsPassed = true;

    // Run each test case
    foreach ($testCases as $testNum) {
        $sectionsXmlPath = "$testDir/{$testNum}_sections.xml";
        $productsXmlPath = "$testDir/{$testNum}_products.xml";
        $expectedOutputPath = "$testDir/{$testNum}_result.xml";

        // Skip tests with missing files
        if (!file_exists($sectionsXmlPath)) {
            echo "Test $testNum: SKIPPED - Missing sections file\n";
            continue;
        }

        if (!file_exists($productsXmlPath)) {
            echo "Test $testNum: SKIPPED - Missing products file\n";
            continue;
        }

        if (!file_exists($expectedOutputPath)) {
            echo "Test $testNum: SKIPPED - Missing expected output file\n";
            continue;
        }

        // Run merge and compare results
        $actualOutput = mergeCatalog($sectionsXmlPath, $productsXmlPath);
        $expectedOutput = file_get_contents($expectedOutputPath);

        $normalizedActual = normalizeXml($actualOutput);
        $normalizedExpected = normalizeXml($expectedOutput);

        if ($normalizedActual === $normalizedExpected) {
            echo "Test $testNum: PASSED\n";
        } else {
            echo "Test $testNum: FAILED\n";
            $allTestsPassed = false;

            // Save actual output for debugging
            file_put_contents("output_$testNum.xml", $actualOutput);
            echo "Output saved to output_$testNum.xml for inspection\n";

            // Show differences (truncated for brevity)
            $diffLimit = 200;
            $diff = substr(diff($normalizedExpected, $normalizedActual), 0, $diffLimit);
            if (strlen($diff) >= $diffLimit) {
                $diff .= "... (truncated)";
            }
            echo "Differences: $diff\n";
        }
    }

    // Summarize test results
    if ($allTestsPassed) {
        echo "All tests passed!\n";
    } else {
        echo "Some tests failed. Please check the output above.\n";
    }

    return $allTestsPassed;
}

/**
 * Normalizes XML string by removing XML declaration and excess whitespace.
 *
 * Prepares XML strings for comparison by standardizing formatting, removing
 * the XML declaration, and collapsing multiple spaces.
 *
 * @param string $xml Input XML string
 * @return string Normalized XML string
 */
function normalizeXml($xml)
{
    $xml = preg_replace('/<\?xml[^>]+\?>/', '', $xml);
    $xml = preg_replace('/\s+/', ' ', $xml);
    $xml = trim($xml);

    return $xml;
}

/**
 * Compares two strings and generates a diff report.
 *
 * Identifies character-level differences between two strings, including position
 * and mismatched characters, and reports length mismatches.
 *
 * @param string $str1 Expected string
 * @param string $str2 Actual string
 * @return string Diff report detailing mismatches
 */
function diff($str1, $str2)
{
    $str1 = str_split($str1);
    $str2 = str_split($str2);
    $len1 = count($str1);
    $len2 = count($str2);
    $max = min($len1, $len2);

    $result = '';
    for ($i = 0; $i < $max; $i++) {
        if ($str1[$i] !== $str2[$i]) {
            $result .= "at position $i: expected '{$str1[$i]}', got '{$str2[$i]}'\n";
        }
    }

    if ($len1 !== $len2) {
        $result .= "Length mismatch: expected $len1, got $len2\n";
    }

    return $result;
}

/**
 * Handles command-line execution for merging XML files.
 *
 * If run with 'run' argument, merges sections.xml and products.xml and saves
 * the result to output.xml.
 */
if (count($argv) > 1 && $argv[1] === 'run') {
    $output = mergeCatalog('sections.xml', 'products.xml');
    file_put_contents('output.xml', $output);
    echo "Merged XML saved to output.xml\n";
} else {
    runTests();
}
