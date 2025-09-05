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
- ✅ Supports all PHP 8.4 bcmath functions including `bcfloor()`, `bcceil()`, and `bcround()`
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

## ⚡ Performance

This polyfill uses [phpseclib](https://github.com/phpseclib/phpseclib)'s BigInteger class for arbitrary precision arithmetic, providing reliable performance for applications that cannot use the native bcmath extension.

## ⚠️ Known Limitations

### Extension Detection
- `extension_loaded('bcmath')` will return `false` when using the polyfill
- Recommended approach: Don't check for the extension, just use the functions

### Configuration Options
- `ini_set('bcmath.scale', ...)` won't work without the native extension
- Use `bcscale()` instead to set the scale globally
- To get the current scale:
  - PHP >= 7.3.0: Use `bcscale()` without arguments
  - PHP < 7.3.0: Use `max(0, strlen(bcadd('0', '0')) - 2)`

## 🔄 Key Differences from phpseclib/bcmath_compat

| Feature | phpseclib/bcmath_compat | bcmath-polyfill |
|---------|------------------------|-----------------|
| **PHP 8.4 functions** | ❌ Not supported | ✅ Full support |
| `bcfloor()` | ❌ | ✅ |
| `bcceil()` | ❌ | ✅ |
| `bcround()` | ❌ | ✅ |
| **PHP 8.2+ deprecations** | ⚠️ Warnings | ✅ Fixed |
| **Test suite pollution** | ⚠️ Issues | ✅ Fixed |
| **Active maintenance** | ❌ Limited | ✅ Active |
| **CI/CD (PHP versions)** | GitHub Actions (8.1, 8.2, 8.3) | GitHub Actions (8.1, 8.2, 8.3, 8.4) |

### Migration from phpseclib/bcmath_compat

Switching is seamless - no code changes required:

```bash
# Remove old package
composer remove phpseclib/bcmath_compat

# Install bcmath-polyfill
composer require nanasess/bcmath-polyfill
```

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