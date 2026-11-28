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
- ✅ Supports all PHP 8.4+ bcmath functions including `bcfloor()`, `bcceil()`, `bcround()`, and `bcdivmod()`
- ✅ PHP 8.5 and 8.6 compatibility tested and verified
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
echo bcround('2.5', 0, \RoundingMode::TowardsZero);       // 2
echo bcround('2.5', 0, \RoundingMode::AwayFromZero);      // 3
echo bcround('2.5', 0, \RoundingMode::PositiveInfinity);  // 3
echo bcround('2.5', 0, \RoundingMode::NegativeInfinity);  // 2

// bcdivmod() returns [quotient, remainder]
[$quot, $rem] = bcdivmod('17', '5');  // ['3', '2']
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
- `bcdivmod()` - Get the quotient and remainder of a division in a single call

#### RoundingMode Enum Support
The `bcround()` function supports PHP 8.4's `RoundingMode` enum through a polyfill for PHP 8.1-8.3.
All eight native modes are implemented with exact, arbitrary-precision string arithmetic (no float
fallback), so results match the native `bcround()` even for very large or high-precision numbers:

- `RoundingMode::HalfAwayFromZero` (equivalent to `PHP_ROUND_HALF_UP`)
- `RoundingMode::HalfTowardsZero` (equivalent to `PHP_ROUND_HALF_DOWN`)
- `RoundingMode::HalfEven` (equivalent to `PHP_ROUND_HALF_EVEN`)
- `RoundingMode::HalfOdd` (equivalent to `PHP_ROUND_HALF_ODD`)
- `RoundingMode::TowardsZero`
- `RoundingMode::AwayFromZero`
- `RoundingMode::NegativeInfinity`
- `RoundingMode::PositiveInfinity`

The legacy integer `PHP_ROUND_*` constants are also accepted for the four half-modes.

> **Note:** The polyfilled `RoundingMode` is a *pure* enum, matching native PHP 8.4 (it has no
> backing value; `->value` / `from()` / `tryFrom()` are intentionally unavailable).

#### Interoperability with `symfony/polyfill-php84`

`symfony/polyfill-php84` also declares `bcceil()`, `bcfloor()`, `bcround()` and `bcdivmod()`, but only
when the native bcmath extension is **already loaded** (its purpose is to backport the new PHP 8.4
functions to older PHP that already has bcmath). This package instead provides the full bcmath API for
environments **without** the extension.

When both packages are installed on an environment that *does* have the bcmath extension and runs
**PHP 8.2/8.3**, both declare `bcceil()`/`bcfloor()`/`bcround()`; whichever autoloads first wins (the
`function_exists()` guards prevent a fatal redeclaration). This package's rounding is implemented with a
native-compatible string algorithm, so the numeric results are identical regardless of which
implementation is used. On environments without the extension only this package is active.

## ⚡ Performance

The polyfill picks the fastest way it has for each call:

- In `bcadd()`, `bcsub()`, `bcmul()`, `bcdiv()`, `bcmod()` and `bcdivmod()`, numbers that fit in a PHP `int` (up to 18 significant digits on 64-bit PHP, 9 on 32-bit; for `bcmul()` the digits of both operands together, for `bcdiv()` the dividend's digits plus `$scale`) are computed with `int` arithmetic.
- `bcadd()`, `bcsub()` and `bccomp()` work on the digit strings directly, so they are fast at any size, with or without ext-gmp.
- Everything else uses [ext-gmp](https://www.php.net/manual/en/book.gmp.php) when it is loaded (directly, except `bcpowmod()`, which goes through phpseclib's GMP engine), and otherwise [phpseclib](https://github.com/phpseclib/phpseclib)'s BigInteger in pure PHP.

**Install ext-gmp if you work with large numbers.** Without it, multiplication, division, `bcsqrt()`, `bcpow()` and `bcpowmod()` of large numbers are hundreds of times slower than native ([#127](https://github.com/nanasess/bcmath-polyfill/issues/127)).

### Rough Guide

Time per call, measured with PHP 8.5 on x86-64 (AMD Ryzen, Zen 3) and phpseclib 3.0.47. The numbers depend on the machine; use the ratio to native as the guide.

| Operation | With ext-gmp | Without ext-gmp | Native bcmath |
|---|---|---|---|
| `bcadd()` / `bcmul()` / `bcdiv()` / `bccomp()`, small numbers (`'1234.5678'`, `'987.65'`) | ~2 µs (~20x) | ~2 µs (~20x) | ~0.1 µs |
| `bcadd()`, 1,000 digits | 13 µs (~35x) | 13 µs (~35x) | 0.4 µs |
| `bccomp()`, 1,000 digits | 2.4 µs (~15x) | 2.4 µs (~15x) | 0.16 µs |
| `bcmul()`, 1,000 digits | 15 µs (~2x) | 3,600 µs (~460x) | 7.9 µs |
| `bcdiv()`, 1,000 digits | 7.3 µs (~5x) | 1,300 µs (~950x) | 1.4 µs |
| `bcsqrt()`, 1,000 digits, scale 50 | 7.4 µs (faster) | 9,000 µs (~33x) | 273 µs |
| `bcpow('2', '1000')` | 2.5 µs (~2x) | 139 µs (~200x) | 0.7–1.4 µs |
| `bcround()` / `bcfloor()` | ~1.4 µs (~20x) | ~1.4 µs (~20x) | ~0.07 µs |

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

# Measure without ext-gmp (phpseclib's pure-PHP engine)
php benchmarks/run-benchmarks.php -e PHP64

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

Each line shows the total time of all iterations for the native function and the polyfill, and their ratio. The `BigInteger engine` line shows whether the polyfill used ext-gmp (`GMP`) or the pure-PHP engine (`PHP64`). Excerpt of a run with ext-gmp:

```
BCMath Performance Benchmark
========================================
Iterations per test: 10000
Native BCMath: Available
BigInteger engine: GMP
========================================

Basic Operations
----------------

  Small numbers (10 digits):
    bcadd      | Native:    0.881ms (    0B) | Polyfill:   17.584ms (    0B) | Ratio: 20.0x slower
    ...

High Precision Operations
-------------------------

  Scale 100:
    bcdiv      | Native:    3.120ms (    0B) | Polyfill:   27.134ms (    0B) | Ratio: 8.7x slower
    bcsqrt     | Native:   43.680ms (    0B) | Polyfill:   23.617ms (    0B) | Ratio: 1.8x faster

Summary
========================================
Average performance ratio: 6.62x
Polyfill is on average 6.6x slower than native
```

The same run with `-e PHP64` averages about 230x slower than native, mostly from multiplication, division, `bcpow()` and `bcsqrt()`.

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
- Multi-PHP version testing (8.1, 8.2, 8.3, 8.4, 8.5, 8.6)
- Results saved as artifacts

### Performance Considerations

- For numbers that fit in an `int` (see above), the polyfill is about 20x slower than native, whichever engine is used. This is mostly argument validation and normalization, and is adequate for most applications.
- With large numbers, install ext-gmp. With it, multiplication and division are within a few times of native, and `bcsqrt()` and some `bcpow()` calls (such as `bcpow('1.0001', '10000', 20)`) can be faster than native.
- For performance-critical code, use the native bcmath extension.

## ⚠️ Known Limitations

### Extension Detection
- `extension_loaded('bcmath')` will return `false` when using the polyfill
- Recommended approach: Don't check for the extension, just use the functions

### Configuration Options
- **bcmath.scale INI setting is only read at startup**: When the native bcmath extension is not loaded, PHP does not register the `bcmath.scale` INI entry. The polyfill reads the value set in php.ini or with `-d bcmath.scale=N` (through `get_cfg_var()`) as the initial scale, but:
  - `ini_get('bcmath.scale')` returns `false`
  - `ini_set('bcmath.scale', ...)` won't work, and `bcscale()` does not update `ini_get('bcmath.scale')`
  - On PHP 8.2+, the value is parsed like the native extension (`010` is 8, `0x10` is 16, `1k` is 1024); on PHP 8.1 only decimal integers are accepted and anything else falls back to 0
  - An invalid value does not raise the warning the native extension raises at startup
- **Workaround**: Use `bcscale()` instead to change the scale at runtime
- To get the current scale:
  - PHP >= 7.3.0: Use `bcscale()` without arguments
  - PHP < 7.3.0: Use `max(0, strlen(bcadd('0', '0')) - 2)`

### Behavior Follows PHP 8.4+
The polyfill behaves like the native bcmath extension of PHP 8.4 and later on every PHP version it supports.
On PHP 8.1-8.3 this differs from the native extension of that version in a few cases (#110):

| Call | Native PHP 8.1 / 8.2 | Native PHP 8.3 | Native PHP 8.4+ and the polyfill |
|---|---|---|---|
| `bcpow('0', '-2', 2)` | `'0.00'` | `'0.00'` | `DivisionByZeroError` (Negative power of zero) |
| `bcpow('2', '3.000')` | `ValueError` (cannot have a fractional part) | `'8'` | `'8'` |
| `bcpowmod('5', '3', '7.000')` | `ValueError` (cannot have a fractional part) | `'6'` | `'6'` |
| `bcpowmod('5', '0', '-1')` | `'1'` on 8.1, `'0'` on 8.2 | `'0'` | `'0'` |

## 🔄 Key Differences from phpseclib/bcmath_compat and symfony/polyfill-php84

The three libraries target different goals. `phpseclib/bcmath_compat` is the classic pre-8.4 polyfill.
`symfony/polyfill-php84` backports **only** the new PHP 8.4 functions and activates them **only when the
native bcmath extension is already loaded**. `bcmath-polyfill` provides the complete bcmath API —
including the PHP 8.4 additions — for environments **with or without** the extension.

| Feature                                        | phpseclib/bcmath_compat        | symfony/polyfill-php84          | bcmath-polyfill                          |
|------------------------------------------------|--------------------------------|---------------------------------|------------------------------------------|
| Classic bc functions (`bcadd`, `bcmul`, …)     | ✅                             | ❌ Not provided                 | ✅                                       |
| Works **without** the bcmath extension         | ✅                             | ❌ Requires the extension       | ✅                                       |
| `bcfloor()` / `bcceil()` / `bcround()`         | ❌                             | ✅ (extension required)         | ✅                                       |
| `bcdivmod()`                                    | ❌                             | ✅ (delegates to native bc)     | ✅ (works without the extension)         |
| **RoundingMode enum** (all 8 modes)            | ❌                             | ✅                              | ✅                                       |
| Arbitrary-precision rounding (no float fallback)| ❌                            | ✅ (pure string algorithm)      | ✅ (pure string algorithm)               |
| `RoundingMode` is a *pure* enum (native shape) | —                              | ✅                              | ✅                                       |
| Implementation of rounding                     | —                              | Pure string digits             | Pure string digits (phpseclib elsewhere) |
| Extra dependency                               | phpseclib                      | none                            | phpseclib                                |
| **PHP 8.2+ deprecations**                      | ⚠️ Warnings                    | ✅ Fixed                        | ✅ Fixed                                 |
| **Active maintenance**                         | ❌ Limited                     | ✅ Active                       | ✅ Active                                |
| **CI/CD (PHP versions)**                       | GitHub Actions (8.1, 8.2, 8.3) | 7.2+                            | GitHub Actions (8.1, 8.2, 8.3, 8.4, 8.5, 8.6) |

> When `bcmath-polyfill` and `symfony/polyfill-php84` are installed together on an environment that has
> the bcmath extension and runs PHP 8.2/8.3, both declare `bcceil()`/`bcfloor()`/`bcround()` and whichever
> autoloads first wins (guarded by `function_exists()`, so no fatal redeclaration). Because this package's
> rounding uses a native-compatible string algorithm, the results are identical either way. See
> [Interoperability with `symfony/polyfill-php84`](#interoperability-with-symfonypolyfill-php84).

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

### Differential Fuzzing

`tests/BCMathFuzzTest.php` compares the functions with the native bcmath extension on random inputs (the extension is required). `bcscale()` itself is not compared, and `bcdivmod()`, `bcfloor()`, `bcceil()` and `bcround()` are skipped on PHP versions whose extension does not have them. It runs with a fixed seed as part of the normal test suite, and with more iterations and a new seed every week in the `Fuzz` workflow.

```bash
# Run with another seed and more iterations
BCMATH_FUZZ_SEED=12345 BCMATH_FUZZ_ITERATIONS=100000 vendor/bin/phpunit --group fuzz

# Print every case before it runs (to find an input that crashes or hangs)
BCMATH_FUZZ_TRACE=1 vendor/bin/phpunit --filter 'BCMathFuzzTest::testPowmod'
```

A failure prints the seed, the arguments, and both results. Known differences are skipped by `knownIssue()` in the test, each with the issue that tracks it. The rules marked `intended` are not bugs: on PHP 8.1-8.3 the polyfill follows PHP 8.4+ (see [Behavior Follows PHP 8.4+](#behavior-follows-php-84)).

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
- PHP 8.1, 8.2, 8.3, 8.4, 8.5, 8.6

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
