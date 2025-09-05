#!/bin/bash

# PHP-src BCMath Tests Runner
# This script runs php-src BCMath tests against the polyfill
#
# Usage:
#   ./scripts/run-php-src-tests.sh [--skip test1,test2,...]
#
# Prerequisites:
# - php-src repository should be checked out in ./php-src/
# - Run these commands first:
#   mkdir -p tests/php-src
#   cp php-src/ext/bcmath/tests/*.phpt tests/php-src/
#   cp php-src/ext/bcmath/tests/*.inc tests/php-src/
#
# Examples:
#   ./scripts/run-php-src-tests.sh --skip bcdivmod,bcpowmod
#   ./scripts/run-php-src-tests.sh

set -e

# Parse command line arguments
SKIP_TESTS=""
while [[ $# -gt 0 ]]; do
    case $1 in
        --skip)
            SKIP_TESTS="$2"
            shift 2
            ;;
        *)
            echo "Unknown option: $1"
            echo "Usage: $0 [--skip test1,test2,...]"
            exit 1
            ;;
    esac
done

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "=== PHP-src BCMath Tests Runner ==="
echo

# Check if we're in the correct directory
if [ ! -f "composer.json" ]; then
    echo -e "${RED}Error: Please run this script from the project root directory${NC}"
    exit 1
fi

# Check if test files exist
if [ ! -d "tests/php-src" ] || [ -z "$(ls -A tests/php-src/*.phpt 2>/dev/null)" ]; then
    echo -e "${RED}Error: php-src test files not found${NC}"
    echo "Please run the following commands first:"
    echo "  mkdir -p tests/php-src"
    echo "  cp php-src/ext/bcmath/tests/*.phpt tests/php-src/"
    echo "  cp php-src/ext/bcmath/tests/*.inc tests/php-src/"
    exit 1
fi

cd tests/php-src

# Create common test helper
echo "Creating common test helper..."
cat > test_helper.php << 'EOF'
<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bcmath.php';

define('STRING_PADDING', 30);

if (!function_exists('run_bcmath_tests')) {
    function run_bcmath_tests(array $firstTerms, array $secondTerms, string $symbol, callable $bcmath_function): void
    {
        foreach ([0, 10] as $scale) {
            echo "Scale: {$scale}\n";
            foreach ($firstTerms as $firstTerm) {
                foreach ($secondTerms as $secondTerm) {
                    try {
                        $result = $bcmath_function($firstTerm, $secondTerm, $scale);
                        echo $firstTerm . ' ' . $symbol . ' ' . str_pad($secondTerm, STRING_PADDING, ' ', STR_PAD_LEFT) . ' = ' . $result . "\n";
                    } catch (Exception $e) {
                        echo $firstTerm . ' ' . $symbol . ' ' . str_pad($secondTerm, STRING_PADDING, ' ', STR_PAD_LEFT) . ' Exception: ' . $e->getMessage() . "\n";
                    }
                }
            }
            echo "\n";
        }
    }
}
EOF

# Clean up any previously generated PHP files (but keep test_helper.php)
echo "Cleaning up previously generated PHP files..."
find . -name "*.php" -not -name "test_helper.php" -delete

# Convert phpt tests to standalone PHP scripts
echo "Converting phpt tests to PHP scripts..."
php -r "
\$testDir = '.';
\$files = glob(\$testDir . '/*.phpt');

foreach (\$files as \$file) {
    \$basename = basename(\$file);
    if (\$basename === 'run_bcmath_tests_function.inc' || strpos(\$basename, 'run_bcmath_tests') !== false) continue;

    echo 'Processing ' . basename(\$file) . PHP_EOL;

    \$content = file_get_contents(\$file);
    \$sections = [];
    \$currentSection = null;

    foreach (explode(PHP_EOL, \$content) as \$line) {
        if (preg_match('/^--([A-Z]+)--$/', \$line, \$matches)) {
            \$currentSection = \$matches[1];
            \$sections[\$currentSection] = '';
        } elseif (\$currentSection) {
            \$sections[\$currentSection] .= \$line . PHP_EOL;
        }
    }

    if (isset(\$sections['FILE'])) {
        \$phpCode = trim(\$sections['FILE']);
        \$testName = basename(\$file, '.phpt');
        \$outputFile = \$testDir . '/' . \$testName . '.php';

        // Remove any require/include statements for run_bcmath_tests_function.inc
        \$phpCode = preg_replace('/^\s*require(?:_once)?\s*.*run_bcmath_tests_function\.inc.*$/m', '', \$phpCode);
        \$phpCode = preg_replace('/^\s*include(?:_once)?\s*.*run_bcmath_tests_function\.inc.*$/m', '', \$phpCode);

        // Check if this test uses run_bcmath_tests function
        \$needsTestFunction = strpos(\$phpCode, 'run_bcmath_tests(') !== false;

        if (\$needsTestFunction) {
            \$wrappedCode = '<?php' . PHP_EOL .
                'require_once __DIR__ . \'/test_helper.php\';' . PHP_EOL . PHP_EOL .
                trim(str_replace('<?php', '', \$phpCode));
        } else {
            \$wrappedCode = '<?php' . PHP_EOL .
                'require_once __DIR__ . \'/../../vendor/autoload.php\';' . PHP_EOL .
                'require_once __DIR__ . \'/../../lib/bcmath.php\';' . PHP_EOL . PHP_EOL .
                trim(str_replace('<?php', '', \$phpCode));
        }

        file_put_contents(\$outputFile, \$wrappedCode);
        echo 'Created: ' . \$outputFile . PHP_EOL;
    }
}
"

echo
echo "Running php-src BCMath compatibility tests..."
echo "=============================================="

# Convert comma-separated skip list to array
IFS=',' read -ra SKIP_ARRAY <<< "$SKIP_TESTS"

# Function to check if a test should be skipped
should_skip_test() {
    local test_name="$1"
    local base_name="${test_name%.php}"

    for skip_pattern in "${SKIP_ARRAY[@]}"; do
        skip_pattern=$(echo "$skip_pattern" | xargs) # trim whitespace
        if [[ "$base_name" == *"$skip_pattern"* ]]; then
            return 0  # should skip
        fi
    done
    return 1  # should not skip
}

failed_tests=0
total_tests=0
passed_tests=0
skipped_tests=0

for test_file in *.php; do
    if [ "$test_file" = "test_helper.php" ]; then
        continue
    fi
    # Skip files that contain run_bcmath_tests in the name
    if [[ "$test_file" == *"run_bcmath_tests"* ]]; then
        continue
    fi

    # Check if this test should be skipped
    if should_skip_test "$test_file"; then
        echo -e "${YELLOW}⏭️  Skipping: $test_file (not implemented)${NC}"
        skipped_tests=$((skipped_tests + 1))
        echo "----------------------------------------"
        continue
    fi

    echo -e "${YELLOW}Running: $test_file${NC}"
    total_tests=$((total_tests + 1))

    if timeout 30s php "$test_file" > /dev/null 2>&1; then
        echo -e "${GREEN}✅ Test $test_file PASSED${NC}"
        passed_tests=$((passed_tests + 1))
    else
        echo -e "${RED}❌ Test $test_file FAILED${NC}"
        echo "   Error output:"
        timeout 30s php "$test_file" 2>&1 | head -5 | sed 's/^/   /'
        failed_tests=$((failed_tests + 1))
    fi
    echo "----------------------------------------"
done

echo
echo "=============================================="
echo -e "Test Results: ${GREEN}$passed_tests${NC}/${total_tests} passed"

if [ $skipped_tests -gt 0 ]; then
    echo -e "${YELLOW}⏭️  $skipped_tests test(s) skipped (not implemented)${NC}"
fi

if [ $failed_tests -gt 0 ]; then
    echo -e "${RED}❌ $failed_tests test(s) failed${NC}"
    echo
    echo "To see detailed error output for a specific test:"
    echo "  cd tests/php-src && php <test_name>.php"
    exit 1
else
    echo -e "${GREEN}✅ All executed tests passed${NC}"
fi

echo
echo "Test files are available in tests/php-src/ for individual inspection."

if [ -n "$SKIP_TESTS" ]; then
    echo
    echo "Skipped tests: $SKIP_TESTS"
fi
