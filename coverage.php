<?php

$inputFile = __DIR__ . '/.phpunit.cache/coverage.txt';
if (!is_file($inputFile)) {
    echo "No coverage data found. Run tests first.\n";
    exit(1);
}
$data = file($inputFile);
if (empty($data)) {
    echo "Coverage data not readable.\n";
    exit(1);
}
$coverage = array_map('trim', $data);
$coverage = array_filter($coverage, fn($line) => !empty($line));
$coverage = array_reduce($coverage, function ($carry, $line) {
    if (!str_contains($line, '%')) {
        return $carry;
    }
    preg_match_all('/([a-zA-Z]+):\s+([\d.%]+).*/', $line, $matches);
    if (count($matches) < 3) {
        return $carry;
    }
    $carry[strtolower($matches[1][0])] = trim($matches[2][0]);
    return $carry;
}, []);

if (empty($coverage)) {
    echo "No coverage data found.\n";
    exit(1);
}
$requestedReturnType = strtolower($argv[1] ?? 'lines');
if ($requestedReturnType == 'all') {
    print_r($coverage);
    exit(0);
}
if (!array_key_exists($requestedReturnType, $coverage)) {
    echo "No coverage data for $requestedReturnType.\n";
    exit(1);
}
echo $coverage[$requestedReturnType];
