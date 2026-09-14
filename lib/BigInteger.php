<?php

/**
 * BigInteger class alias for phpseclib 3 / 4 compatibility.
 *
 * phpseclib 4.0 renamed its namespace from `phpseclib3` to `phpseclib4` while
 * keeping the BigInteger API identical. Rather than hard-coding one namespace,
 * `bcmath_compat\Math\BigInteger` is registered here as an alias of whichever
 * implementation is installed, and src/BCMath.php only ever refers to the alias.
 *
 * This file is loaded eagerly via composer's "files" autoload on purpose:
 * - PHP does not trigger autoloading when checking parameter/return types, so a
 *   lazily registered alias would make public methods typed against the alias
 *   (e.g. BCMath::format()) throw a TypeError when called before any other
 *   polyfill method has instantiated a BigInteger.
 * - A PSR-4 file that only calls class_alias() is invisible to the classmap, so
 *   it is never found under `composer install --classmap-authoritative`.
 *
 * phpseclib 4 is preferred when both namespaces resolve (e.g. a phpseclib3
 * compatibility shim installed alongside phpseclib 4).
 */
if (!class_exists('bcmath_compat\Math\BigInteger', false)) {
    class_alias(
        class_exists('phpseclib4\Math\BigInteger') ? 'phpseclib4\Math\BigInteger' : 'phpseclib3\Math\BigInteger',
        'bcmath_compat\Math\BigInteger'
    );
}
