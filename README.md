# bcmath-polyfill

<p align="center">
  <strong>PHP 8.4 bcmath functions polyfill with fallback compatibility for environments without bcmath extension.</strong>
</p>

<p align="center">
  <a href="LICENSE.md"><img src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square" alt="Software License"></a>
  <a href="https://github.com/nanasess/bcmath-polyfill/actions/workflows/ci.yml?query=branch%3Amain"><img src="https://github.com/nanasess/bcmath-polyfill/actions/workflows/ci.yml/badge.svg?branch=main&event=push" alt="CI Status"></a>
  <a href="#"><img src="https://img.shields.io/badge/PHPStan-level%20max-brightgreen.svg?style=flat-square" alt="PHPStan Level Max"></a>
  <a href="https://packagist.org/packages/nanasess/bcmath-polyfill"><img src="https://img.shields.io/packagist/v/nanasess/bcmath-polyfill.svg?style=flat-square" alt="Latest Version"></a>
  <a href="https://packagist.org/packages/nanasess/bcmath-polyfill"><img src="https://img.shields.io/packagist/dt/nanasess/bcmath-polyfill.svg?style=flat-square" alt="Total Downloads"></a>
</p>

---

## 🚀 Features

- ✅ Complete bcmath extension polyfill for PHP 8.1+
- ✅ Supports all PHP 8.4+ bcmath functions including `bcfloor()`, `bcceil()`, and `bcround()`
- ✅ PHP 8.5 compatibility tested and verified
- ✅ Zero dependencies in production (uses phpseclib for arbitrary precision math)
- ✅ Seamless fallback when bcmath extension is not available
- ✅ 100% compatible with native bcmath functions

## 📦 Installation

Install via [Composer](https://getcomposer.org/):

```bash
composer require nanasess/bcmath-polyfill
```

## 🔧 Usage

Simply include the polyfill in your project and use bcmath functions as you normally would:

```php
// The polyfill will automatically load when bcmath extension is not available
require_once 'vendor/autoload.php';

// All bcmath functions work identically to the native extension
echo bcadd('1.234', '5.678', 2);    // 6.91
echo bcmul('2.5', '3.5', 1);        // 8.7
echo bcpow('2', '8');               // 256

// PHP 8.4 functions are also supported
echo bcfloor('4.7');                // 4
echo bcceil('4.3');                 // 5
echo bcround('3.14159', 2);         // 3.14

// bcround() supports RoundingMode enum (PHP 8.1+ with polyfill, native in PHP 8.4+)
echo bcround('2.5', 0, \RoundingMode::HalfAwayFromZero);  // 3
echo bcround('2.5', 0, \RoundingMode::HalfTowardsZero);   // 2
echo bcround('2.5', 0, \RoundingMode::HalfEven);          // 2
echo bcround('2.5', 0, \RoundingMode::HalfOdd);           // 3
```

## 📋 Supported Functions

### Classic bcmath Functions
- `bcadd()` - Add two arbitrary precision numbers
- `bcsub()` - Subtract one arbitrary precision number from another
- `bcmul()` - Multiply two arbitrary precision numbers
- `bcdiv()` - Divide two arbitrary precision numbers
- `bcmod()` - Get modulus of an arbitrary precision number
- `bcpow()` - Raise an arbitrary precision number to another
- `bcsqrt()` - Get the square root of an arbitrary precision number
- `bcscale()` - Set/get default scale parameter
- `bccomp()` - Compare two arbitrary precision numbers
- `bcpowmod()` - Raise an arbitrary precision number to another, reduced by a specified modulus

### PHP 8.4 Functions (Added in [PR #6](https://github.com/nanasess/bcmath-polyfill/pull/6))
- `bcfloor()` - Round down to the nearest integer
- `bcceil()` - Round up to the nearest integer
- `bcround()` - Round to a specified precision with configurable rounding modes

#### RoundingMode Enum Support
The `bcround()` function supports PHP 8.4's `RoundingMode` enum through a polyfill for PHP 8.1-8.3:

**Supported Modes:**
- `RoundingMode::HalfAwayFromZero` (equivalent to `PHP_ROUND_HALF_UP`)
- `RoundingMode::HalfTowardsZero` (equivalent to `PHP_ROUND_HALF_DOWN`)
- `RoundingMode::HalfEven` (equivalent to `PHP_ROUND_HALF_EVEN`)
- `RoundingMode::HalfOdd` (equivalent to `PHP_ROUND_HALF_ODD`)

**Not Yet Supported:**
- `RoundingMode::TowardsZero` - Throws `ValueError` (planned for future release)
- `RoundingMode::AwayFromZero` - Throws `ValueError` (planned for future release)  
- `RoundingMode::NegativeInfinity` - Throws `ValueError` (planned for future release)

## ⚡ Performance

This polyfill uses [phpseclib](https://github.com/phpseclib/phpseclib)'s BigInteger class for arbitrary precision arithmetic, providing reliable performance for applications that cannot use the native bcmath extension.

### Performance Benchmarking

This project includes a comprehensive benchmarking tool to compare the performance of the polyfill against native bcmath functions.

#### Local Benchmark Execution

```bash
# Run benchmark with default settings (10,000 iterations)
composer benchmark
# or
php benchmarks/run-benchmarks.php

# Export results in different formats
php benchmarks/run-benchmarks.php -f json -o results.json
php benchmarks/run-benchmarks.php -f csv -o results.csv
php benchmarks/run-benchmarks.php -f markdown -o BENCHMARK.md

# Show help
php benchmarks/run-benchmarks.php --help
```

#### Benchmark Configuration

The benchmark tests include:
- **Basic Operations**: Addition, subtraction, multiplication, division with various number sizes
- **Advanced Operations**: Power, square root, modulo operations
- **Large Numbers**: Operations with 100, 500, and 1000 digit numbers
- **High Precision**: Operations with scale values of 20, 50, and 100

#### Interpreting Results

Typical performance comparison shows:
- **Basic operations**: Polyfill is ~100-250x slower than native
- **Large numbers**: Performance gap increases with number size (up to ~800x slower)
- **Square root**: Interestingly, polyfill can be faster for high-precision operations

Example output:
```
BCMath Performance Benchmark
========================================
Iterations per test: 10000
Native BCMath: Available

Basic Operations
----------------
  Small numbers (10 digits):
    bcadd      | Native:    1.5ms | Polyfill:  230ms | Ratio: 150x slower
    ...

Summary
========================================
Average performance ratio: 26x
Polyfill is on average 26x slower than native
```

### GitHub Actions Integration

The project includes automated benchmarking workflows:

#### 1. PR Benchmarks (`/benchmark` command)
Comment `/benchmark` on any PR to run performance tests:
- Automatically triggered on PR updates to relevant files
- Quick mode (1,000 iterations) for fast feedback
- Results posted as PR comment

#### 2. Merge Benchmarks
Automatically runs when PRs are merged to main:
- Full benchmark (10,000 iterations)
- Results posted to the merged PR for reference

#### 3. Manual Benchmarks
Trigger via GitHub Actions UI:
- Customizable iteration count
- Multi-PHP version testing (8.1, 8.2, 8.3, 8.4, 8.5)
- Results saved as artifacts

### Performance Considerations

While the polyfill is significantly slower than native bcmath:
- It provides full functionality when bcmath extension is unavailable
- Performance is adequate for most applications not requiring intensive calculations
- Consider using native bcmath for performance-critical applications

## ⚠️ Known Limitations

### Extension Detection
- `extension_loaded('bcmath')` will return `false` when using the polyfill
- Recommended approach: Don't check for the extension, just use the functions

### Configuration Options
- **bcmath.scale INI setting is ignored**: When the native bcmath extension is not loaded, PHP does not recognize the `bcmath.scale` INI setting. This means:
  - `ini_get('bcmath.scale')` returns `false`
  - `ini_set('bcmath.scale', ...)` won't work
  - INI settings in php.ini or PHPT tests are ignored
  - The polyfill defaults to scale 0 when no explicit scale is provided
- **Workaround**: Use `bcscale()` instead to set the scale globally
- To get the current scale:
  - PHP >= 7.3.0: Use `bcscale()` without arguments
  - PHP < 7.3.0: Use `max(0, strlen(bcadd('0', '0')) - 2)`

### RoundingMode Enum Limitations
- Three `RoundingMode` enum values are not yet implemented:
  - `RoundingMode::TowardsZero` - Will throw `ValueError`
  - `RoundingMode::AwayFromZero` - Will throw `ValueError`
  - `RoundingMode::NegativeInfinity` - Will throw `ValueError`
- These modes are planned for implementation in a future release
- Use traditional `PHP_ROUND_*` constants as alternatives when needed

## 🔄 Key Differences from phpseclib/bcmath_compat

| Feature                   | phpseclib/bcmath_compat        | bcmath-polyfill                     |
|---------------------------|--------------------------------|-------------------------------------|
| **PHP 8.4 functions**     | ❌ Not supported               | ✅ Full support                     |
| `bcfloor()`               | ❌                             | ✅                                  |
| `bcceil()`                | ❌                             | ✅                                  |
| `bcround()`               | ❌                             | ✅                                  |
| **RoundingMode enum**     | ❌ Not supported               | ✅ Partial (4/7 modes)              |
| `HalfAwayFromZero`        | ❌                             | ✅                                  |
| `HalfTowardsZero`         | ❌                             | ✅                                  |
| `HalfEven`                | ❌                             | ✅                                  |
| `HalfOdd`                 | ❌                             | ✅                                  |
| `TowardsZero`             | ❌                             | ⏳ Planned                          |
| `AwayFromZero`            | ❌                             | ⏳ Planned                          |
| `NegativeInfinity`        | ❌                             | ⏳ Planned                          |
| **PHP 8.2+ deprecations** | ⚠️ Warnings                     | ✅ Fixed                            |
| **Test suite pollution**  | ⚠️ Issues                       | ✅ Fixed                            |
| **Active maintenance**    | ❌ Limited                     | ✅ Active                           |
| **CI/CD (PHP versions)**  | GitHub Actions (8.1, 8.2, 8.3) | GitHub Actions (8.1, 8.2, 8.3, 8.4, 8.5) |

### Migration from phpseclib/bcmath_compat

Switching is seamless - no code changes required:

```bash
# Remove old package
composer remove phpseclib/bcmath_compat

# Install bcmath-polyfill
composer require nanasess/bcmath-polyfill
```

## 🧪 Testing

### Running PHPUnit Tests

```bash
# Install dependencies
composer install

# Run all tests
composer test
# or
vendor/bin/phpunit

# Run tests without bcmath extension
vendor/bin/phpunit --group without-bcmath
```

### Docker-based PHPT Testing

This project includes comprehensive Docker-based testing using official PHP core bcmath tests to ensure 100% compatibility:

```bash
# Build Docker test environment (PHP 8.3 by default)
docker build -f Dockerfile.test-without-bcmath -t bcmath-phpt-test .

# Run all PHPT tests
docker run --rm -v $PWD:/app bcmath-phpt-test

# Build and test with specific PHP version
docker build -f Dockerfile.test-without-bcmath --build-arg PHP_VERSION=8.4 -t bcmath-phpt-test:8.4 .
docker run --rm -v $PWD:/app bcmath-phpt-test:8.4

# Skip specific tests (supports exact test name matching)
docker run --rm -v $PWD:/app bcmath-phpt-test --skip bcceil,bcround,bcpowmod

# Run specific test file
docker run --rm -v $PWD:/app bcmath-phpt-test tests/php-src/bcadd.phpt

# Show help
docker run --rm bcmath-phpt-test --help
```

#### Available Options

- `--skip TESTS` - Comma-separated list of test names to skip (exact matching)
- `--help, -h` - Show usage information

#### Supported PHP Versions
- PHP 8.1, 8.2, 8.3, 8.4, 8.5

The Docker PHPT tests automatically run on GitHub Actions CI across all supported PHP versions to ensure comprehensive compatibility with the official PHP core bcmath test suite.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.

## 🙏 Credits

This project is a fork of [phpseclib/bcmath_compat](https://github.com/phpseclib/bcmath_compat), originally created by the phpseclib team. We've extended it with PHP 8.4 function support and continue to maintain compatibility with all PHP versions.

---

<p align="center">
  Made with ❤️ for the PHP community
</p>
