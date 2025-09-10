#!/bin/bash
set -e

# Parse command line options
SKIP_TESTS=""
TEST_FILES=()

while [[ $# -gt 0 ]]; do
    case $1 in
        --skip)
            SKIP_TESTS="$2"
            shift 2
            ;;
        --help|-h)
            echo "Usage: $0 [OPTIONS] [TEST_FILES...]"
            echo ""
            echo "Options:"
            echo "  --skip TESTS    Comma-separated list of test patterns to skip"
            echo "                  (e.g., --skip bcround,bcdivmod,bug60377)"
            echo "  --help, -h      Show this help message"
            echo ""
            echo "Examples:"
            echo "  $0                              # Run all tests"
            echo "  $0 tests/php-src/bcadd.phpt    # Run specific test"
            echo "  $0 --skip bcround tests/php-src/bcadd*.phpt  # Skip bcround tests, run bcadd tests"
            echo "  $0 --skip bcround,bcdivmod      # Skip multiple test patterns"
            exit 0
            ;;
        *)
            TEST_FILES+=("$1")
            shift
            ;;
    esac
done

echo "=== PHPT Test Runner (without bcmath extension) ==="
echo "bcmath extension loaded: $(php -r "var_dump(extension_loaded('bcmath'));")"

if [ -n "$SKIP_TESTS" ]; then
    echo "Skipping tests matching: $SKIP_TESTS"
fi

# Install dependencies if needed (for volume mounting)
if [ ! -d "vendor" ]; then
    echo "Installing composer dependencies..."
    composer install --optimize-autoloader --no-dev -q
fi

# Prepare test environment
if [ -d "php-src" ]; then
    echo "Setting up PHPT test environment..."
    mkdir -p tests/php-src

    echo "Copying test files from php-src..."
    if cp php-src/ext/bcmath/tests/*.phpt tests/php-src/ 2>/dev/null; then
        echo "Successfully copied .phpt files"
    else
        echo "Error: Could not copy .phpt files from php-src/ext/bcmath/tests/"
        exit 1
    fi

    if cp php-src/ext/bcmath/tests/*.inc tests/php-src/ 2>/dev/null; then
        echo "Successfully copied .inc files"
    else
        echo "Warning: Could not copy .inc files (may not exist)"
    fi

    if cp php-src/run-tests.php ./ 2>/dev/null; then
        echo "Successfully copied run-tests.php"
    else
        echo "Error: Could not copy run-tests.php from php-src/"
        exit 1
    fi

    # Remove --EXTENSIONS-- sections (integrated function)
    remove_extensions_from_phpt() {
        local phpt_dir="$1"

        if [ ! -d "$phpt_dir" ]; then
            echo "Error: Directory $phpt_dir does not exist"
            return 1
        fi

        echo "Removing --EXTENSIONS-- sections from PHPT files in $phpt_dir..."

        local processed=0
        for phpt_file in "$phpt_dir"/*.phpt; do
            if [ -f "$phpt_file" ]; then
                echo "Processing $(basename "$phpt_file")"

                # Create temporary file
                local temp_file=$(mktemp)

                # Remove --EXTENSIONS-- section completely
                awk '
                BEGIN { in_extensions = 0 }
                /^--EXTENSIONS--$/ {
                    in_extensions = 1
                    next
                }
                /^--[A-Z]+--$/ && in_extensions {
                    in_extensions = 0
                    print
                    next
                }
                !in_extensions { print }
                ' "$phpt_file" > "$temp_file"

                # Replace original file
                mv "$temp_file" "$phpt_file"
                processed=$((processed + 1))
            fi
        done

        echo "Processed $processed PHPT files"
        echo "All --EXTENSIONS-- sections have been removed"
    }

    remove_extensions_from_phpt tests/php-src

    # Add bootstrap to each PHPT file
    echo "Adding bootstrap to PHPT files..."
    for phpt_file in tests/php-src/*.phpt; do
        if [ -f "$phpt_file" ]; then
            # Create backup
            cp "$phpt_file" "$phpt_file.bak"

            # Add bootstrap at the beginning of the --FILE-- section
            awk '
            /^--FILE--$/ {
                print
                getline
                print "<?php"
                print "require_once \"/app/vendor/autoload.php\";"
                print "require_once \"/app/lib/bcmath.php\";"
                if ($0 == "<?php") {
                    # Skip the original <?php line
                    next
                }
                print
                next
            }
            { print }
            ' "$phpt_file.bak" > "$phpt_file"

            rm "$phpt_file.bak"
        fi
    done
    echo "Added bootstrap to PHPT files"
fi

# Test if polyfill file exists
if [ ! -f "lib/bcmath.php" ]; then
    echo "Error: lib/bcmath.php not found. Make sure you've mounted the project correctly."
    exit 1
fi

# Test polyfill functionality using -d option
echo "Testing polyfill functionality:"
echo "Debug: Checking file contents..."
echo "lib/bcmath.php exists: $([ -f 'lib/bcmath.php' ] && echo 'YES' || echo 'NO')"
echo "lib/bcmath.php first 5 lines:"
head -5 lib/bcmath.php

echo "Debug: Testing with composer autoloader + polyfill..."
php -r "
require_once '/app/vendor/autoload.php';
require_once '/app/lib/bcmath.php';
if (function_exists('bcadd')) {
    echo 'SUCCESS: bcadd function available after autoloader + polyfill' . PHP_EOL;
    echo 'bcadd(1, 2) = ' . bcadd('1', '2') . PHP_EOL;
} else {
    echo 'ERROR: bcadd function NOT available after autoloader + polyfill' . PHP_EOL;
}
"

echo "Debug: Testing with bootstrap file..."
php -d auto_prepend_file=/app/scripts/polyfill-bootstrap.php -r "
echo 'auto_prepend_file setting: ' . ini_get('auto_prepend_file') . PHP_EOL;
if (function_exists('bcadd')) {
    echo 'SUCCESS: bcadd(1, 2) = ' . bcadd('1', '2') . PHP_EOL;
} else {
    echo 'ERROR: bcadd function not available after bootstrap' . PHP_EOL;
}
"

# Set environment for run-tests.php
export TEST_PHP_EXECUTABLE="/usr/local/bin/php"

# Run PHPT tests
if [ -f "run-tests.php" ] && [ -d "tests/php-src" ]; then
    echo "Running PHPT tests with EXPECT validation..."

    # Function to check if a test should be skipped
    should_skip_test() {
        local test_file="$1"
        local base_name=$(basename "$test_file" .phpt)

        if [ -z "$SKIP_TESTS" ]; then
            return 1  # Don't skip
        fi

        # Convert comma-separated list to array
        IFS=',' read -ra SKIP_ARRAY <<< "$SKIP_TESTS"

        for skip_pattern in "${SKIP_ARRAY[@]}"; do
            skip_pattern=$(echo "$skip_pattern" | xargs) # trim whitespace
            if [[ "$base_name" == *"$skip_pattern"* ]]; then
                return 0  # Should skip
            fi
        done
        return 1  # Don't skip
    }

    # Build test file list based on arguments and skip patterns
    FINAL_TEST_FILES=()

    if [ ${#TEST_FILES[@]} -gt 0 ]; then
        # Specific test files provided
        echo "Processing specific test files..."
        for test_file in "${TEST_FILES[@]}"; do
            if should_skip_test "$test_file"; then
                echo "Skipping: $test_file (matches skip pattern)"
            else
                FINAL_TEST_FILES+=("$test_file")
            fi
        done
    else
        # No specific files, process all files in tests/php-src/
        echo "Processing all test files in tests/php-src/..."
        for test_file in tests/php-src/*.phpt; do
            if [ -f "$test_file" ] && ! should_skip_test "$test_file"; then
                FINAL_TEST_FILES+=("$test_file")
            elif should_skip_test "$test_file"; then
                echo "Skipping: $test_file (matches skip pattern)"
            fi
        done
    fi

    # Run the tests
    if [ ${#FINAL_TEST_FILES[@]} -gt 0 ]; then
        echo "Running ${#FINAL_TEST_FILES[@]} test file(s):"
        printf '  %s\n' "${FINAL_TEST_FILES[@]}"
        php run-tests.php \
            --show-diff \
            --no-clean \
            "${FINAL_TEST_FILES[@]}"
    else
        echo "No tests to run (all tests were skipped or no matching files found)"
    fi
else
    echo "Error: run-tests.php or tests/php-src not found"
    echo "Make sure to:"
    echo "  1. Mount your project directory with -v \$PWD:/app"
    echo "  2. Have php-src/ directory in your project"
    echo "  3. Run the container to set up test files"
    exit 1
fi
