<?php
function mergeCatalog($sectionsXmlPath, $productsXmlPath)
{
    $sectionsXml = simplexml_load_file($sectionsXmlPath);
    $productsXml = simplexml_load_file($productsXmlPath);

    $outputXml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><ЭлементыКаталога><Разделы></Разделы></ЭлементыКаталога>');

    foreach ($sectionsXml->Раздел as $section) {
        $sectionId = (string) $section->Ид;

        $outputSection = $outputXml->Разделы->addChild('Раздел');
        $outputSection->addChild('Ид', $sectionId);
        $outputSection->addChild('Наименование', (string) $section->Наименование);

        $outputProducts = $outputSection->addChild('Товары');

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

                if ($belongsToSection) {
                    $outputProduct = $outputProducts->addChild('Товар');
                    $outputProduct->addChild('Ид', (string) $product->Ид);
                    $outputProduct->addChild('Наименование', (string) $product->Наименование);
                    $outputProduct->addChild('Артикул', (string) $product->Артикул);
                }
            }
        }
    }

    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($outputXml->asXML());

    return $dom->saveXML();
}

function runTests()
{
    $testDir = './test';

    if (!is_dir($testDir)) {
        echo "Error: Test directory '$testDir' not found\n";
        return false;
    }

    $sectionFiles = glob("$testDir/*_sections.xml");

    if (empty($sectionFiles)) {
        echo "No test files found in '$testDir'\n";
        return false;
    }

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

    foreach ($testCases as $testNum) {
        $sectionsXmlPath = "$testDir/{$testNum}_sections.xml";
        $productsXmlPath = "$testDir/{$testNum}_products.xml";
        $expectedOutputPath = "$testDir/{$testNum}_result.xml";

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

        $actualOutput = mergeCatalog($sectionsXmlPath, $productsXmlPath);
        $expectedOutput = file_get_contents($expectedOutputPath);

        $normalizedActual = normalizeXml($actualOutput);
        $normalizedExpected = normalizeXml($expectedOutput);

        if ($normalizedActual === $normalizedExpected) {
            echo "Test $testNum: PASSED\n";
        } else {
            echo "Test $testNum: FAILED\n";
            $allTestsPassed = false;

            file_put_contents("output_$testNum.xml", $actualOutput);
            echo "Output saved to output_$testNum.xml for inspection\n";

            $diffLimit = 200;
            $diff = substr(diff($normalizedExpected, $normalizedActual), 0, $diffLimit);
            if (strlen($diff) >= $diffLimit) {
                $diff .= "... (truncated)";
            }
            echo "Differences: $diff\n";
        }
    }

    if ($allTestsPassed) {
        echo "All tests passed!\n";
    } else {
        echo "Some tests failed. Please check the output above.\n";
    }

    return $allTestsPassed;
}

function normalizeXml($xml)
{
    $xml = preg_replace('/<\?xml[^>]+\?>/', '', $xml);

    $xml = preg_replace('/\s+/', ' ', $xml);
    $xml = trim($xml);

    return $xml;
}

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

if (count($argv) > 1 && $argv[1] === 'run') {
    $output = mergeCatalog('sections.xml', 'products.xml');
    file_put_contents('output.xml', $output);
    echo "Merged XML saved to output.xml\n";
} else {
    runTests();
}
?>