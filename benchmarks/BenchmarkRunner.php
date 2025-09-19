<?php

namespace bcmath_compat\Benchmarks;

use bcmath_compat\BCMath;

class BenchmarkRunner
{
    private const ITERATIONS = 10000;
    private const WARMUP_ITERATIONS = 1000;

    private array $results = [];
    private bool $nativeAvailable;

    public function __construct()
    {
        $this->nativeAvailable = extension_loaded('bcmath');
    }

    public function run(): void
    {
        $this->printHeader();

        // Basic operations
        $this->benchmarkBasicOperations();

        // Advanced operations
        $this->benchmarkAdvancedOperations();

        // Large number operations
        $this->benchmarkLargeNumbers();

        // High precision operations
        $this->benchmarkHighPrecision();

        $this->printSummary();
    }

    private function benchmarkBasicOperations(): void
    {
        $this->printSection('Basic Operations');

        $testCases = [
            ['name' => 'Small numbers (10 digits)', 'a' => '1234567890', 'b' => '9876543210', 'scale' => 0],
            ['name' => 'Decimals (scale=10)', 'a' => '123.4567890123', 'b' => '987.6543210987', 'scale' => 10],
            ['name' => 'Medium numbers (50 digits)', 'a' => str_repeat('12345', 10), 'b' => str_repeat('98765', 10), 'scale' => 0],
        ];

        foreach ($testCases as $testCase) {
            $this->printSubSection($testCase['name']);

            // Addition
            $this->benchmark('bcadd', function() use ($testCase) {
                if ($this->nativeAvailable) {
                    return \bcadd($testCase['a'], $testCase['b'], $testCase['scale']);
                }
            }, function() use ($testCase) {
                return BCMath::add($testCase['a'], $testCase['b'], $testCase['scale']);
            }, $testCase['name']);

            // Subtraction
            $this->benchmark('bcsub', function() use ($testCase) {
                if ($this->nativeAvailable) {
                    return \bcsub($testCase['a'], $testCase['b'], $testCase['scale']);
                }
            }, function() use ($testCase) {
                return BCMath::sub($testCase['a'], $testCase['b'], $testCase['scale']);
            }, $testCase['name']);

            // Multiplication
            $this->benchmark('bcmul', function() use ($testCase) {
                if ($this->nativeAvailable) {
                    return \bcmul($testCase['a'], $testCase['b'], $testCase['scale']);
                }
            }, function() use ($testCase) {
                return BCMath::mul($testCase['a'], $testCase['b'], $testCase['scale']);
            }, $testCase['name']);

            // Division
            $this->benchmark('bcdiv', function() use ($testCase) {
                if ($this->nativeAvailable) {
                    return \bcdiv($testCase['a'], $testCase['b'], $testCase['scale']);
                }
            }, function() use ($testCase) {
                return BCMath::div($testCase['a'], $testCase['b'], $testCase['scale']);
            }, $testCase['name']);
        }
    }

    private function benchmarkAdvancedOperations(): void
    {
        $this->printSection('Advanced Operations');

        $testCases = [
            ['name' => 'Power (small exponent)', 'base' => '2', 'exp' => '10', 'scale' => 0],
            ['name' => 'Power (large base)', 'base' => '12345', 'exp' => '5', 'scale' => 0],
            ['name' => 'Square root', 'number' => '123456789', 'scale' => 10],
            ['name' => 'Modulo', 'a' => '1234567890', 'b' => '123', 'scale' => 0],
        ];

        // Power operations
        $powerCases = array_filter($testCases, fn($c) => isset($c['exp']));
        foreach ($powerCases as $testCase) {
            $this->printSubSection($testCase['name']);

            $this->benchmark('bcpow', function() use ($testCase) {
                if ($this->nativeAvailable) {
                    return \bcpow($testCase['base'], $testCase['exp'], $testCase['scale']);
                }
            }, function() use ($testCase) {
                return BCMath::pow($testCase['base'], $testCase['exp'], $testCase['scale']);
            }, $testCase['name']);
        }

        // Square root
        $sqrtCase = array_filter($testCases, fn($c) => isset($c['number']))[2];
        $this->printSubSection($sqrtCase['name']);

        $this->benchmark('bcsqrt', function() use ($sqrtCase) {
            if ($this->nativeAvailable) {
                return \bcsqrt($sqrtCase['number'], $sqrtCase['scale']);
            }
        }, function() use ($sqrtCase) {
            return BCMath::sqrt($sqrtCase['number'], $sqrtCase['scale']);
        }, $sqrtCase['name']);

        // Modulo
        $modCase = array_filter($testCases, fn($c) => isset($c['a']) && isset($c['b']))[3];
        $this->printSubSection($modCase['name']);

        $this->benchmark('bcmod', function() use ($modCase) {
            if ($this->nativeAvailable) {
                return \bcmod($modCase['a'], $modCase['b'], $modCase['scale']);
            }
        }, function() use ($modCase) {
            return BCMath::mod($modCase['a'], $modCase['b'], $modCase['scale']);
        }, $modCase['name']);
    }

    private function benchmarkLargeNumbers(): void
    {
        $this->printSection('Large Number Operations');

        $testCases = [
            ['name' => '100 digits', 'digits' => 100],
            ['name' => '500 digits', 'digits' => 500],
            ['name' => '1000 digits', 'digits' => 1000],
        ];

        foreach ($testCases as $testCase) {
            $this->printSubSection($testCase['name']);

            $a = $this->generateNumber($testCase['digits']);
            $b = $this->generateNumber($testCase['digits']);

            // Addition
            $this->benchmark('bcadd', function() use ($a, $b) {
                if ($this->nativeAvailable) {
                    return \bcadd($a, $b);
                }
            }, function() use ($a, $b) {
                return BCMath::add($a, $b);
            }, $testCase['name']);

            // Multiplication (with smaller numbers to avoid timeout)
            if ($testCase['digits'] <= 500) {
                $smallA = substr($a, 0, 50);
                $smallB = substr($b, 0, 50);

                $this->benchmark('bcmul', function() use ($smallA, $smallB) {
                    if ($this->nativeAvailable) {
                        return \bcmul($smallA, $smallB);
                    }
                }, function() use ($smallA, $smallB) {
                    return BCMath::mul($smallA, $smallB);
                }, $testCase['name'] . ' (first 50 digits)');
            }
        }
    }

    private function benchmarkHighPrecision(): void
    {
        $this->printSection('High Precision Operations');

        $testCases = [
            ['name' => 'Scale 20', 'scale' => 20],
            ['name' => 'Scale 50', 'scale' => 50],
            ['name' => 'Scale 100', 'scale' => 100],
        ];

        foreach ($testCases as $testCase) {
            $this->printSubSection($testCase['name']);

            $a = '123.456789';
            $b = '987.654321';

            // Division (most affected by scale)
            $this->benchmark('bcdiv', function() use ($a, $b, $testCase) {
                if ($this->nativeAvailable) {
                    return \bcdiv($a, $b, $testCase['scale']);
                }
            }, function() use ($a, $b, $testCase) {
                return BCMath::div($a, $b, $testCase['scale']);
            }, $testCase['name']);

            // Square root
            $this->benchmark('bcsqrt', function() use ($a, $testCase) {
                if ($this->nativeAvailable) {
                    return \bcsqrt($a, $testCase['scale']);
                }
            }, function() use ($a, $testCase) {
                return BCMath::sqrt($a, $testCase['scale']);
            }, $testCase['name']);
        }
    }

    private function benchmark(string $operation, callable $native, callable $polyfill, string $context = ''): void
    {
        $nativeTime = null;
        $nativeMemory = null;

        if ($this->nativeAvailable && $native !== null) {
            // Warmup
            for ($i = 0; $i < self::WARMUP_ITERATIONS; $i++) {
                $native();
            }

            // Benchmark
            $memBefore = memory_get_usage(true);
            $start = microtime(true);
            for ($i = 0; $i < self::ITERATIONS; $i++) {
                $native();
            }
            $nativeTime = (microtime(true) - $start) * 1000; // Convert to milliseconds
            $nativeMemory = memory_get_usage(true) - $memBefore;
        }

        // Warmup polyfill
        for ($i = 0; $i < self::WARMUP_ITERATIONS; $i++) {
            $polyfill();
        }

        // Benchmark polyfill
        $memBefore = memory_get_usage(true);
        $start = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $polyfill();
        }
        $polyfillTime = (microtime(true) - $start) * 1000; // Convert to milliseconds
        $polyfillMemory = memory_get_usage(true) - $memBefore;

        $this->printResult($operation, $nativeTime, $polyfillTime, $nativeMemory, $polyfillMemory);

        $this->results[] = [
            'operation' => $operation,
            'context' => $context,
            'native_time' => $nativeTime,
            'polyfill_time' => $polyfillTime,
            'native_memory' => $nativeMemory,
            'polyfill_memory' => $polyfillMemory,
        ];
    }

    private function generateNumber(int $digits): string
    {
        $number = '';
        for ($i = 0; $i < $digits; $i++) {
            $number .= mt_rand(0, 9);
        }
        return ltrim($number, '0') ?: '0';
    }

    private function printHeader(): void
    {
        echo "\n";
        echo "BCMath Performance Benchmark\n";
        echo "========================================\n";
        echo "Iterations per test: " . self::ITERATIONS . "\n";
        echo "Native BCMath: " . ($this->nativeAvailable ? 'Available' : 'Not Available') . "\n";
        echo "========================================\n\n";
    }

    private function printSection(string $title): void
    {
        echo "\n";
        echo "$title\n";
        echo str_repeat('-', strlen($title)) . "\n\n";
    }

    private function printSubSection(string $title): void
    {
        echo "  $title:\n";
    }

    private function printResult(string $operation, ?float $nativeTime, float $polyfillTime, ?int $nativeMemory, int $polyfillMemory): void
    {
        $format = "    %-10s | ";
        printf($format, $operation);

        if ($this->nativeAvailable && $nativeTime !== null) {
            printf("Native: %8.3fms (%6s) | ", $nativeTime, $this->formatBytes($nativeMemory));
            printf("Polyfill: %8.3fms (%6s) | ", $polyfillTime, $this->formatBytes($polyfillMemory));

            $ratio = $polyfillTime / $nativeTime;
            $slower = $ratio > 1 ? sprintf("%.1fx slower", $ratio) : sprintf("%.1fx faster", 1/$ratio);
            printf("Ratio: %s", $slower);
        } else {
            printf("Polyfill: %8.3fms (%6s)", $polyfillTime, $this->formatBytes($polyfillMemory));
        }

        echo "\n";
    }

    private function printSummary(): void
    {
        echo "\n";
        echo "Summary\n";
        echo "========================================\n";

        if (!$this->nativeAvailable) {
            echo "Native BCMath extension not available.\n";
            echo "Only polyfill performance measured.\n";
            return;
        }

        $totalNative = 0;
        $totalPolyfill = 0;
        $count = 0;

        foreach ($this->results as $result) {
            if ($result['native_time'] !== null) {
                $totalNative += $result['native_time'];
                $totalPolyfill += $result['polyfill_time'];
                $count++;
            }
        }

        if ($count > 0) {
            $avgRatio = ($totalPolyfill / $totalNative);
            printf("Average performance ratio: %.2fx\n", $avgRatio);

            if ($avgRatio > 1) {
                printf("Polyfill is on average %.1fx slower than native\n", $avgRatio);
            } else {
                printf("Polyfill is on average %.1fx faster than native\n", 1/$avgRatio);
            }
        }

        echo "\n";
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) return '0B';

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $i), 2) . $units[$i];
    }

    public function exportResults(string $format = 'json'): string
    {
        switch ($format) {
            case 'json':
                return json_encode($this->results, JSON_PRETTY_PRINT);

            case 'csv':
                $csv = "Operation,Native Time (ms),Polyfill Time (ms),Native Memory (bytes),Polyfill Memory (bytes),Ratio\n";
                foreach ($this->results as $result) {
                    $ratio = ($result['native_time'] !== null) ? $result['polyfill_time'] / $result['native_time'] : 0;
                    $csv .= sprintf("%s,%f,%f,%d,%d,%f\n",
                        $result['operation'],
                        $result['native_time'] ?? 0,
                        $result['polyfill_time'],
                        $result['native_memory'] ?? 0,
                        $result['polyfill_memory'],
                        $ratio
                    );
                }
                return $csv;

            case 'markdown':
                $md = "# BCMath Performance Benchmark Results\n\n";
                $md .= "| Operation | Context | Native Time | Polyfill Time | Ratio |\n";
                $md .= "|-----------|---------|-------------|---------------|-------|\n";
                foreach ($this->results as $result) {
                    if ($result['native_time'] !== null) {
                        $ratio = $result['polyfill_time'] / $result['native_time'];
                        $md .= sprintf("| %s | %s | %.3fms | %.3fms | %.2fx |\n",
                            $result['operation'],
                            $result['context'],
                            $result['native_time'],
                            $result['polyfill_time'],
                            $ratio
                        );
                    }
                }
                return $md;

            default:
                throw new \InvalidArgumentException("Unsupported format: $format");
        }
    }
}