#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use bcmath_compat\Benchmarks\BenchmarkRunner;

// Parse command line arguments
$options = getopt('f:o:e:h', ['format:', 'output:', 'engine:', 'help']);

if (isset($options['h']) || isset($options['help'])) {
    showHelp();
    exit(0);
}

$format = $options['f'] ?? $options['format'] ?? null;
$output = $options['o'] ?? $options['output'] ?? null;
$engine = $options['e'] ?? $options['engine'] ?? null;

// Create and run benchmark
try {
    $benchmark = new BenchmarkRunner($engine);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
$benchmark->run();

// Export results if requested
if ($format !== null) {
    try {
        $results = $benchmark->exportResults($format);

        if ($output !== null) {
            file_put_contents($output, $results);
            echo "\nResults exported to: $output\n";
        } else {
            echo "\n" . $results . "\n";
        }
    } catch (\InvalidArgumentException $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}

function showHelp(): void
{
    echo <<<HELP
BCMath Performance Benchmark Tool

Usage: php benchmarks/run-benchmarks.php [OPTIONS]

Options:
  -f, --format FORMAT   Export results in specified format (json, csv, markdown)
  -o, --output FILE     Save results to file (instead of stdout)
  -e, --engine ENGINE   phpseclib BigInteger engine for the polyfill (GMP, PHP64, PHP32).
                        Use PHP64 to measure an environment without ext-gmp.
  -h, --help           Show this help message

Examples:
  # Run benchmark with console output only
  php benchmarks/run-benchmarks.php

  # Export results as JSON
  php benchmarks/run-benchmarks.php -f json -o results.json

  # Export results as Markdown
  php benchmarks/run-benchmarks.php -f markdown -o BENCHMARK.md

  # Export results as CSV
  php benchmarks/run-benchmarks.php -f csv -o results.csv

  # Measure the pure-PHP engine, as on a system without ext-gmp
  php benchmarks/run-benchmarks.php -e PHP64

HELP;
}