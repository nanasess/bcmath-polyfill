<?php

use bcmath_compat\BCMath;
use bcmath_compat\Math\BigInteger;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

/**
 * Differential fuzzing against the native bcmath extension.
 *
 * Random inputs are generated from a fixed seed and every function of the
 * polyfill is compared with the native implementation: the return value must
 * be identical, and when the native function throws or raises a warning the
 * polyfill must throw the same class with the same message (or raise the same
 * warning).
 *
 * Environment variables:
 *  - BCMATH_FUZZ_SEED: base seed (default: self::DEFAULT_SEED). Every test
 *    method reseeds with it, so a failure is reproduced by running the same
 *    method with the same seed.
 *  - BCMATH_FUZZ_ITERATIONS: iterations per test method (default:
 *    self::DEFAULT_ITERATIONS).
 *  - BCMATH_FUZZ_TRACE: when non-zero, print every case to STDERR before it
 *    runs, to find the input when the process crashes or hangs.
 *
 * The generators also produce inputs with known differences. Those cases are
 * skipped by knownIssue(), which names the issue that tracks each difference;
 * remove a rule there when its issue is fixed. Rules marked "intended" are
 * differences the polyfill keeps on purpose (it follows PHP 8.4+, see #110).
 *
 * @see https://github.com/nanasess/bcmath-polyfill/issues/102
 *
 * @internal
 */
#[RequiresPhpExtension('bcmath')]
#[CoversNothing]
#[Group('fuzz')]
final class BCMathFuzzTest extends TestCase
{
    private const DEFAULT_SEED = 102;

    private const DEFAULT_ITERATIONS = 2000;

    /** Report at most this many mismatches per test method. */
    private const MAX_REPORTED = 10;

    private int $seed;

    private int $iterations;

    private bool $trace;

    /** @var string[] */
    private array $originalEngine;

    protected function setUp(): void
    {
        $this->seed = $this->intFromEnv('BCMATH_FUZZ_SEED', self::DEFAULT_SEED);
        $this->iterations = max(1, $this->intFromEnv('BCMATH_FUZZ_ITERATIONS', self::DEFAULT_ITERATIONS));
        $this->trace = $this->intFromEnv('BCMATH_FUZZ_TRACE', 0) !== 0;
        $this->originalEngine = BigInteger::getEngine();
        $this->useEngineWithoutBcmath();
        mt_srand($this->seed);
    }

    protected function tearDown(): void
    {
        BigInteger::setEngine($this->originalEngine[0], [$this->originalEngine[1]]);
    }

    public function testAdd(): void
    {
        $this->fuzzBinary('bcadd', BCMath::add(...));
    }

    public function testSub(): void
    {
        $this->fuzzBinary('bcsub', BCMath::sub(...));
    }

    public function testMul(): void
    {
        $this->fuzzBinary('bcmul', BCMath::mul(...));
    }

    public function testDiv(): void
    {
        $this->fuzzBinary('bcdiv', BCMath::div(...));
    }

    public function testMod(): void
    {
        $this->fuzzBinary('bcmod', BCMath::mod(...));
    }

    public function testComp(): void
    {
        $this->fuzzBinary('bccomp', BCMath::comp(...));
    }

    public function testDivmod(): void
    {
        $this->requireNativeFunction('bcdivmod');
        $this->fuzzBinary('bcdivmod', BCMath::divmod(...));
    }

    public function testPow(): void
    {
        $this->fuzz('bcpow', BCMath::pow(...), fn (): array => [
            $this->number(maxIntDigits: 6, maxFracDigits: 6),
            $this->exponent(),
            $this->scale(),
        ]);
    }

    public function testPowmod(): void
    {
        $this->fuzz('bcpowmod', BCMath::powmod(...), fn (): array => [
            // bcpowmod() rejects fractional operands; generate a few to compare the error.
            $this->chance(3) ? $this->number() : $this->integer(maxDigits: 30, allowNegative: true),
            $this->chance(3) ? $this->number() : $this->integer(maxDigits: 20, allowNegative: $this->chance(5)),
            $this->chance(3) ? $this->number() : $this->modulus(),
            $this->scale(),
        ]);
    }

    public function testSqrt(): void
    {
        $this->fuzz('bcsqrt', BCMath::sqrt(...), fn (): array => [
            // Negative operands are rare so that most cases check the result.
            $this->chance(5) ? $this->number() : ltrim($this->number(), '-'),
            $this->scale(maxLarge: 60),
        ]);
    }

    public function testFloor(): void
    {
        $this->requireNativeFunction('bcfloor');
        $this->fuzz('bcfloor', BCMath::floor(...), fn (): array => [$this->number()]);
    }

    public function testCeil(): void
    {
        $this->requireNativeFunction('bcceil');
        $this->fuzz('bcceil', BCMath::ceil(...), fn (): array => [$this->number()]);
    }

    public function testRound(): void
    {
        $this->requireNativeFunction('bcround');
        // The native bcround() only accepts the RoundingMode enum (PHP 8.4+).
        // The polyfill additionally accepts the legacy PHP_ROUND_* integers for
        // backward compatibility, which cannot be compared with the native one.
        $modes = [
            \RoundingMode::HalfAwayFromZero,
            \RoundingMode::HalfTowardsZero,
            \RoundingMode::HalfEven,
            \RoundingMode::HalfOdd,
            \RoundingMode::TowardsZero,
            \RoundingMode::AwayFromZero,
            \RoundingMode::NegativeInfinity,
            \RoundingMode::PositiveInfinity,
        ];
        $this->fuzz('bcround', BCMath::round(...), function () use ($modes): array {
            $precision = mt_rand(-12, 30);

            return [$this->roundable($precision), $precision, $this->pick($modes)];
        });
    }

    /**
     * The polyfill also accepts the legacy PHP_ROUND_* integers. Each of them is passed
     * to the polyfill, and the RoundingMode case it stands for to the native bcround().
     */
    public function testRoundLegacyMode(): void
    {
        $this->requireNativeFunction('bcround');
        // Each RoundingMode case paired with the PHP_ROUND_* integer it stands for.
        $pairs = [
            [\RoundingMode::HalfAwayFromZero, PHP_ROUND_HALF_UP],
            [\RoundingMode::HalfTowardsZero, PHP_ROUND_HALF_DOWN],
            [\RoundingMode::HalfEven, PHP_ROUND_HALF_EVEN],
            [\RoundingMode::HalfOdd, PHP_ROUND_HALF_ODD],
        ];
        // The generator picks a pair and passes its RoundingMode case as the argument (which the
        // native bcround() and the report get); the polyfill gets the integer of the same pair.
        $legacyMode = PHP_ROUND_HALF_UP;
        $this->fuzz(
            'bcround',
            static function (string $num, int $precision) use (&$legacyMode): string {
                return BCMath::round($num, $precision, $legacyMode);
            },
            function () use ($pairs, &$legacyMode): array {
                [$mode, $legacyMode] = $this->pick($pairs);
                $precision = mt_rand(-12, 30);

                return [$this->roundable($precision), $precision, $mode];
            },
        );
    }

    /**
     * @param callable-string $function
     */
    private function fuzzBinary(string $function, callable $polyfill): void
    {
        $this->fuzz($function, $polyfill, fn (): array => [
            $this->number(),
            $this->number(),
            $this->scale(),
        ]);
    }

    /**
     * Run $iterations cases of $function and compare the native result with the polyfill.
     *
     * A trailing null in the generated arguments means "use the default scale":
     * the argument is omitted and the same random default scale is set on both
     * implementations with bcscale() / BCMath::scale().
     *
     * @param callable-string $function the native function
     * @param callable(): list<mixed> $generate
     */
    private function fuzz(string $function, callable $polyfill, callable $generate): void
    {
        $mismatches = [];
        $count = 0;
        $compared = 0;

        /** @var array<string, int> $skipped */
        $skipped = [];

        // The polyfill keeps its own default scale, which may differ from the native one
        // or be still unset (null), so both are saved and restored separately.
        $originalScale = bcscale();
        $polyfillScale = new \ReflectionProperty(BCMath::class, 'scale');
        $originalPolyfillScale = $polyfillScale->getValue();

        try {
            for ($i = 0; $i < $this->iterations; $i++) {
                $args = $generate();
                $defaultScale = null;
                if ($args !== [] && $args[count($args) - 1] === null) {
                    array_pop($args);
                    $defaultScale = mt_rand(0, 10);
                    bcscale($defaultScale);
                    BCMath::scale($defaultScale);
                }

                $issue = $this->knownIssue($function, $args);
                if ($issue !== null) {
                    $skipped[$issue] = ($skipped[$issue] ?? 0) + 1;

                    continue;
                }

                if ($this->trace) {
                    fprintf(
                        STDERR,
                        "#%d %s(%s)%s\n",
                        $i,
                        $function,
                        implode(', ', array_map($this->describe(...), $args)),
                        $defaultScale === null ? '' : " with bcscale({$defaultScale})",
                    );
                }

                $native = $this->outcome(static fn (): mixed => $function(...$args));
                if ($this->trace) {
                    fwrite(STDERR, "    native returned\n");
                }
                $actual = $this->outcome(static fn (): mixed => $polyfill(...$args));
                $compared++;

                if ($native === $actual) {
                    continue;
                }

                $count++;
                if (count($mismatches) < self::MAX_REPORTED) {
                    $mismatches[] = sprintf(
                        "#%d %s(%s)%s\n    native:   %s\n    polyfill: %s",
                        $i,
                        $function,
                        implode(', ', array_map($this->describe(...), $args)),
                        $defaultScale === null ? '' : " with bcscale({$defaultScale})",
                        $native,
                        $actual,
                    );
                }
            }
        } finally {
            bcscale($originalScale);
            $polyfillScale->setValue(null, $originalPolyfillScale);
        }

        $this->assertGreaterThan(0, $compared, sprintf(
            "No case of %s() was compared: all %d generated cases were skipped as known issues (seed %d).\n"
            .'Skipped as known issues: %s',
            $function,
            $this->iterations,
            $this->seed,
            json_encode($skipped, JSON_UNESCAPED_SLASHES),
        ));

        $this->assertSame(0, $count, sprintf(
            "%d of %d cases differ from the native %s() (seed %d).\n"
            ."Reproduce: BCMATH_FUZZ_SEED=%d BCMATH_FUZZ_ITERATIONS=%d vendor/bin/phpunit --filter '%s::%s'\n"
            ."Skipped as known issues: %s\n\n%s",
            $count,
            $this->iterations,
            $function,
            $this->seed,
            $this->seed,
            $this->iterations,
            self::class,
            $this->name(),
            $skipped === [] ? 'none' : json_encode($skipped, JSON_UNESCAPED_SLASHES),
            implode("\n", $mismatches),
        ));
    }

    /**
     * Run $call and describe its return value, exception and warnings as a string.
     */
    private function outcome(callable $call): string
    {
        $warnings = [];
        set_error_handler(static function (int $errno, string $message) use (&$warnings): bool {
            $warnings[] = "warning({$errno}): {$message}";

            return true;
        });

        try {
            $result = 'return '.var_export($call(), true);
        } catch (\Throwable $e) {
            $result = 'throw '.$e::class.': '.$e->getMessage();
        } finally {
            restore_error_handler();
        }

        return implode('; ', [...$warnings, $result]);
    }

    /**
     * The issue that tracks a known difference for these arguments, or null.
     *
     * Cases with a known difference are skipped before either implementation
     * is called, because some of them crash the process. Remove a rule when its
     * issue is fixed so that the fuzzer covers those inputs again. Rules marked
     * "intended" stay: on PHP 8.1-8.3 the polyfill follows PHP 8.4+ on purpose (#110).
     *
     * @param list<mixed> $args
     */
    private function knownIssue(string $function, array $args): ?string
    {
        $numbers = array_values(array_filter($args, is_string(...)));

        if (!$this->allWellFormed($numbers)) {
            return null;
        }

        return match ($function) {
            'bcpow' => match (true) {
                // Intended differences, not bugs (#110): the polyfill follows PHP 8.4+ on every version.
                // The native extension before 8.3 rejects an integer with a zero fractional part
                // ('3.000'), and before 8.4 returns 0 for a negative power of zero.
                PHP_VERSION_ID < 80300 && str_contains($numbers[1], '.') && !$this->hasFraction($numbers[1]) => 'intended (#110) zero-fraction-integer-before-php83',
                PHP_VERSION_ID < 80400 && $this->isZero($numbers[0]) && str_starts_with($numbers[1], '-') && !$this->isZero($numbers[1]) => 'intended (#110) pow-negative-power-of-zero-before-php84',
                default => null,
            },
            'bcpowmod' => match (true) {
                // Intended differences (#110): see bcpow above. The native extension of PHP 8.1
                // also returns 1 instead of 0 for a zero exponent and a modulus of -1.
                PHP_VERSION_ID < 80300 && $this->anyOf($numbers, static fn (string $n): bool => str_contains($n, '.')) => 'intended (#110) zero-fraction-integer-before-php83',
                PHP_VERSION_ID < 80200 && $this->isZero($numbers[1]) && preg_match('/^-0*1$/', $numbers[2]) === 1 => 'intended (#110) powmod-unit-modulus-before-php82',
                default => null,
            },
            // Several bugs give wrong digits; only errors and zero are compared until they are fixed.
            // A negative scale is rejected before the square root is computed, so it is still compared.
            'bcsqrt' => !str_starts_with($numbers[0], '-') && !$this->isZero($numbers[0]) && ($args[1] ?? 0) >= 0
                ? '#109 sqrt-wrong-result'
                : null,
            default => null,
        };
    }

    /**
     * @param list<string> $numbers
     */
    private function allWellFormed(array $numbers): bool
    {
        return !$this->anyOf($numbers, static fn (string $n): bool => preg_match('/^[+-]?\d*(\.\d*)?$/', $n) !== 1);
    }

    /**
     * @param list<string> $values
     * @param callable(string): bool $predicate
     */
    private function anyOf(array $values, callable $predicate): bool
    {
        foreach ($values as $value) {
            if ($predicate($value)) {
                return true;
            }
        }

        return false;
    }

    private function hasFraction(string $number): bool
    {
        return preg_match('/\.\d*[1-9]/', $number) === 1;
    }

    private function isZero(string $number): bool
    {
        return preg_match('/^[+-]?0*(\.0*)?$/', $number) === 1;
    }

    private function describe(mixed $arg): string
    {
        if ($arg instanceof \UnitEnum) {
            return $arg::class.'::'.$arg->name;
        }

        return var_export($arg, true);
    }

    /**
     * A decimal number string, biased towards the boundaries where bugs have been found:
     * zero and negative zero, leading and trailing zeros, carries (runs of 9) and values
     * that become zero after truncation to the scale.
     */
    private function number(int $maxIntDigits = 40, int $maxFracDigits = 40): string
    {
        if ($this->chance(3)) {
            return $this->malformed();
        }

        if ($this->chance(15)) {
            return $this->pick([
                '0', '-0', '0.0', '-0.0', '0.000', '-0.000', '', '1', '-1', '0.5', '-0.5',
                '0.0000005', '-0.0000005', '9.9999999', '-9.9999999', '00012.3400', '-00012.3400',
                '.5',
                // Forms with an empty run of digits, which the extension reads as written
                '.', '-', '+', '-.', '+.', '5.', '-5.', '+.5',
            ]);
        }

        $roll = mt_rand(1, 100);
        $sign = $roll <= 35 ? '-' : ($roll <= 40 ? '+' : '');
        $int = $this->digits(mt_rand(0, 4) === 0 ? mt_rand(0, $maxIntDigits) : mt_rand(0, min(3, $maxIntDigits)));
        if ($this->chance(10)) {
            $int = str_repeat('0', mt_rand(1, 3)).$int;
        }

        $frac = '';
        if ($maxFracDigits > 0 && $this->chance(65)) {
            $frac = $this->digits(mt_rand(1, $maxFracDigits));
            if ($this->chance(15)) {
                $frac .= str_repeat('0', mt_rand(1, 3));
            }
        }

        // The integer part may be omitted only when there is a fractional part.
        if ($int === '' && $frac === '') {
            $int = '0';
        }

        return $sign.$int.($frac === '' ? '' : '.'.$frac);
    }

    /**
     * A number to round to $precision digits. Exact ties (a 5 just after the rounding
     * position, e.g. '2.5' for precision 0) and the values next to them (e.g. '2.49',
     * '2.51') are mixed in, because random digits almost never hit the cases that tell
     * the rounding modes apart.
     */
    private function roundable(int $precision): string
    {
        $roll = mt_rand(1, 100);
        if ($roll > 40) {
            return $this->number();
        }

        $tail = $roll <= 25 ? '5'.str_repeat('0', mt_rand(0, 2)) : $this->pick(['49', '51', '4999', '5001']);
        $sign = $this->chance(40) ? '-' : '';
        $int = $this->digits(mt_rand(0, 3));
        if ($precision >= 0) {
            return $sign.($int === '' ? '0' : $int).'.'.$this->digits($precision).$tail;
        }

        // A negative precision rounds the integer part: the tail starts -$precision digits left of the point.
        $digits = $int.$tail;
        $point = strlen($int) + (-$precision);
        $digits = str_pad($digits, $point, '0');

        return $sign.substr($digits, 0, $point).(strlen($digits) > $point ? '.'.substr($digits, $point) : '');
    }

    /**
     * An integer string, optionally with a zero fractional part.
     */
    private function integer(int $maxDigits, bool $allowNegative): string
    {
        $sign = $allowNegative && $this->chance(30) ? '-' : '';
        $int = $this->digits(mt_rand(1, $this->chance(25) ? $maxDigits : min(4, $maxDigits)));

        return $sign.$int.($this->chance(10) ? '.'.str_repeat('0', mt_rand(1, 3)) : '');
    }

    /**
     * An exponent for bcpow(): an integer, sometimes with a zero fractional part
     * and occasionally with a non-zero one (which bcpow() rejects).
     */
    private function exponent(): string
    {
        if ($this->chance(10)) {
            return $this->pick(['0', '-0', '0.0', '-0.0', '0.00', '-0.00', '1', '-1', '2.000', '-2.000', '2.5', '-0.5']);
        }

        $exponent = (string) mt_rand(-12, 40);

        return $exponent.($this->chance(10) ? '.'.str_repeat('0', mt_rand(1, 3)) : '');
    }

    /**
     * A modulus for bcpowmod(), including 0, 1 and negative values.
     */
    private function modulus(): string
    {
        if ($this->chance(10)) {
            return $this->pick(['1', '-1', '2', '-2', '0', '00', '-0']);
        }

        return $this->integer(maxDigits: 25, allowNegative: true);
    }

    /**
     * A scale: null (use the default scale) or a small, sometimes large, non-negative int.
     * A negative scale is generated occasionally to compare the ValueError.
     */
    private function scale(int $maxLarge = 40): ?int
    {
        $roll = mt_rand(1, 100);

        return match (true) {
            $roll <= 15 => null,
            $roll <= 35 => 0,
            $roll <= 80 => mt_rand(1, 6),
            $roll <= 98 => mt_rand(7, $maxLarge),
            default => -1,
        };
    }

    /**
     * A string that is not a well-formed number.
     */
    private function malformed(): string
    {
        return $this->pick([
            'abc', '1e3', '1E3', ' 1', '1 ', '1.2.3', '0x1A', '--1', '1-', '1,5', 'INF', 'NAN', '+-1', '1..2',
        ]);
    }

    /**
     * $length random digits, biased towards runs of 9 and 0.
     */
    private function digits(int $length): string
    {
        $digits = '';
        $mode = mt_rand(0, 9);
        for ($i = 0; $i < $length; $i++) {
            $digits .= match ($mode) {
                0 => '9',
                1 => mt_rand(0, 3) === 0 ? (string) mt_rand(0, 9) : '0',
                default => (string) mt_rand(0, 9),
            };
        }

        return $digits;
    }

    /**
     * @phpstan-impure
     */
    private function chance(int $percent): bool
    {
        return mt_rand(1, 100) <= $percent;
    }

    /**
     * @template T
     *
     * @param non-empty-list<T> $values
     *
     * @return T
     */
    private function pick(array $values): mixed
    {
        return $values[mt_rand(0, count($values) - 1)];
    }

    /**
     * Skip unless $function is provided by the native extension, not by lib/bcmath.php.
     */
    private function requireNativeFunction(string $function): void
    {
        if (!function_exists($function) || !(new \ReflectionFunction($function))->isInternal()) {
            $this->markTestSkipped("The native {$function}() is not available on PHP ".PHP_VERSION);
        }
    }

    /**
     * Make phpseclib compute without the bcmath extension.
     *
     * The polyfill only runs where bcmath is missing, but this test needs the
     * extension as the reference. Without GMP, phpseclib picks its BCMath engine
     * (preferred on PHP 8.4+), whose results then depend on the bcscale() that
     * this test changes, which never happens in production.
     */
    private function useEngineWithoutBcmath(): void
    {
        foreach ([['GMP', ['DefaultEngine']], ['PHP64', ['OpenSSL', 'DefaultEngine']], ['PHP32', ['OpenSSL', 'DefaultEngine']]] as [$main, $modexps]) {
            try {
                BigInteger::setEngine($main, $modexps);

                return;
            } catch (\Exception) {
            }
        }

        self::fail('No phpseclib BigInteger engine without bcmath is available');
    }

    private function intFromEnv(string $name, int $default): int
    {
        $value = getenv($name);
        if ($value === false || $value === '') {
            return $default;
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new \InvalidArgumentException("{$name} must be an integer, got '{$value}'");
        }

        return (int) $value;
    }
}
