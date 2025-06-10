#!/usr/bin/env php
<?php
/**
 * Simple test runner for PHP 5.4+ without PHPUnit dependency
 */

// Color codes for output
define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_RESET', "\033[0m");

class SimpleTestRunner
{
    private $passed = 0;
    private $failed = 0;
    private $skipped = 0;
    private $errors = [];
    
    public function run($testClass)
    {
        echo "\nRunning tests for: $testClass\n";
        echo str_repeat('-', 50) . "\n";
        
        $reflection = new ReflectionClass($testClass);
        $instance = $reflection->newInstance();
        
        // Get all public methods that start with 'test'
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            if (strpos($method->name, 'test') !== 0) {
                continue;
            }
            
            // Check if this test method uses a data provider
            $docComment = $method->getDocComment();
            if (preg_match('/@dataProvider\s+(\w+)/', $docComment, $matches)) {
                $dataProvider = $matches[1];
                $this->runTestWithDataProvider($instance, $method, $dataProvider);
            } else {
                $this->runTest($instance, $method);
            }
        }
        
        $this->printSummary();
        
        return $this->failed === 0;
    }
    
    private function runTest($instance, $method, $params = null, $dataSetName = '')
    {
        $testName = $method->name . ($dataSetName ? " with data set $dataSetName" : '');
        
        try {
            // Reset test state
            $instance->expectedException = null;
            $instance->skipped = false;
            $instance->skipMessage = '';
            
            // Run the test
            if ($params !== null) {
                $method->invokeArgs($instance, [$params]);
            } else {
                $method->invoke($instance);
            }
            
            // Check if test was skipped
            if ($instance->skipped) {
                $this->skipped++;
                echo COLOR_YELLOW . "S" . COLOR_RESET;
                return;
            }
            
            // Check if we were expecting an exception
            if ($instance->expectedException !== null) {
                $this->failed++;
                echo COLOR_RED . "F" . COLOR_RESET;
                $this->errors[] = "$testName: Expected exception {$instance->expectedException} was not thrown";
                return;
            }
            
            $this->passed++;
            echo COLOR_GREEN . "." . COLOR_RESET;
            
        } catch (Exception $e) {
            if ($instance->expectedException !== null && 
                ($e instanceof $instance->expectedException || 
                 is_a($e, $instance->expectedException))) {
                $this->passed++;
                echo COLOR_GREEN . "." . COLOR_RESET;
            } else {
                $this->failed++;
                echo COLOR_RED . "F" . COLOR_RESET;
                $this->errors[] = "$testName: " . $e->getMessage() . "\n" . $e->getTraceAsString();
            }
        } catch (Throwable $e) {
            // For PHP 7+ to catch Error types (like DivisionByZeroError)
            if ($instance->expectedException !== null && 
                ($e instanceof $instance->expectedException || 
                 is_a($e, $instance->expectedException))) {
                $this->passed++;
                echo COLOR_GREEN . "." . COLOR_RESET;
            } else {
                $this->failed++;
                echo COLOR_RED . "F" . COLOR_RESET;
                $this->errors[] = "$testName: " . $e->getMessage() . "\n" . $e->getTraceAsString();
            }
        }
    }
    
    private function runTestWithDataProvider($instance, $method, $dataProviderName)
    {
        $dataProviderMethod = new ReflectionMethod(get_class($instance), $dataProviderName);
        $data = $dataProviderMethod->invoke($instance);
        
        foreach ($data as $index => $params) {
            $this->runTest($instance, $method, $params, "#$index");
        }
    }
    
    private function printSummary()
    {
        echo "\n\n";
        echo str_repeat('=', 50) . "\n";
        
        $total = $this->passed + $this->failed + $this->skipped;
        
        if ($this->failed === 0) {
            echo COLOR_GREEN . "PASSED" . COLOR_RESET;
        } else {
            echo COLOR_RED . "FAILED" . COLOR_RESET;
        }
        
        echo " (Passed: $this->passed, Failed: $this->failed, Skipped: $this->skipped, Total: $total)\n";
        
        if (!empty($this->errors)) {
            echo "\nFailures:\n";
            foreach ($this->errors as $i => $error) {
                echo "\n" . ($i + 1) . ") $error\n";
            }
        }
        
        echo str_repeat('=', 50) . "\n";
    }
}

// Simple test case base class
class SimpleTestCase
{
    public $expectedException = null;
    public $skipped = false;
    public $skipMessage = '';
    
    public function assertSame($expected, $actual, $message = '')
    {
        if ($expected !== $actual) {
            throw new Exception(
                ($message ? "$message\n" : '') . 
                "Failed asserting that '" . var_export($actual, true) . 
                "' is identical to '" . var_export($expected, true) . "'"
            );
        }
    }
    
    public function markTestSkipped($message = '')
    {
        $this->skipped = true;
        $this->skipMessage = $message;
    }
    
    public function expectException($exception)
    {
        $this->expectedException = $exception;
    }
    
    public function setExpectedException($exception)
    {
        $this->expectedException = $exception;
    }
}

// Load autoloader
$rootDir = dirname(__DIR__);
if (file_exists($rootDir . '/vendor/autoload.php')) {
    // Use Composer autoloader if available
    require_once $rootDir . '/vendor/autoload.php';
} elseif (file_exists($rootDir . '/autoload.php')) {
    // Use custom autoloader for older PHP versions
    require_once $rootDir . '/autoload.php';
} else {
    // Fallback: manually include required files
    require_once $rootDir . '/lib/bcmath.php';
}

// Run tests
$testFile = __DIR__ . '/BCMathTest-simple.php';
if (!file_exists($testFile)) {
    echo "Test file not found: $testFile\n";
    exit(1);
}

require_once $testFile;

$runner = new SimpleTestRunner();
$success = $runner->run('BCMathTestSimple');

exit($success ? 0 : 1);