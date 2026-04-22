<?php

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversNothing]
final class RoundingModeGuardTest extends TestCase
{
    /**
     * Regression test for issue #56.
     *
     * Rector 2.4.x's scoped polyfill declares a global `class RoundingMode`
     * via `class_alias` on PHP < 8.4. The polyfill in lib/RoundingMode.php
     * must detect this and skip enum declaration.
     */
    #[RequiresPhp('>= 8.1 < 8.4')]
    public function testPolyfillSkipsWhenRoundingModeClassAlreadyDefined(): void
    {
        $result = $this->runFixture(__DIR__.'/fixtures/rector-scenario.php');

        $this->assertSame(0, $result['exitCode'], 'Fixture exited with non-zero status. stderr: '.$result['stderr']);
        $this->assertStringNotContainsString('Cannot declare enum', $result['stderr']);
        $this->assertStringNotContainsString('Fatal error', $result['stderr']);
        $this->assertStringContainsString('OK', $result['stdout']);
    }

    #[RequiresPhp('>= 8.1 < 8.4')]
    public function testPolyfillDefinesEnumOnCleanLoad(): void
    {
        $result = $this->runFixture(__DIR__.'/fixtures/clean-load.php');

        $this->assertSame(0, $result['exitCode'], 'Fixture exited with non-zero status. stderr: '.$result['stderr']);
        $this->assertStringContainsString('ENUM_DEFINED', $result['stdout']);
    }

    /**
     * On PHP 8.4+ the native RoundingMode enum is already registered, so the polyfill
     * guard in lib/RoundingMode.php must short-circuit. This asserts that loading the
     * polyfill produces no fatal, and that the (native) enum is visible afterwards.
     */
    #[RequiresPhp('>= 8.4')]
    public function testPolyfillSkipsOnPhp84WithNativeEnum(): void
    {
        $result = $this->runFixture(__DIR__.'/fixtures/clean-load.php');

        $this->assertSame(0, $result['exitCode'], 'Fixture exited with non-zero status. stderr: '.$result['stderr']);
        $this->assertStringNotContainsString('Cannot declare enum', $result['stderr']);
        $this->assertStringNotContainsString('Fatal error', $result['stderr']);
        $this->assertStringContainsString('ENUM_DEFINED', $result['stdout']);
    }

    /**
     * @return array{stdout: string, stderr: string, exitCode: int}
     */
    private function runFixture(string $fixturePath, int $timeoutSeconds = 10): array
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open([PHP_BINARY, $fixturePath], $descriptors, $pipes);
        if (!is_resource($process)) {
            $this->fail('Failed to spawn PHP subprocess');
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';
        $deadline = microtime(true) + $timeoutSeconds;

        while (true) {
            $chunkOut = stream_get_contents($pipes[1]);
            if ($chunkOut !== false) {
                $stdout .= $chunkOut;
            }
            $chunkErr = stream_get_contents($pipes[2]);
            if ($chunkErr !== false) {
                $stderr .= $chunkErr;
            }

            $status = proc_get_status($process);
            if (!$status['running']) {
                // Drain any remaining buffered output after the process exited.
                $chunkOut = stream_get_contents($pipes[1]);
                if ($chunkOut !== false) {
                    $stdout .= $chunkOut;
                }
                $chunkErr = stream_get_contents($pipes[2]);
                if ($chunkErr !== false) {
                    $stderr .= $chunkErr;
                }

                break;
            }

            if (microtime(true) > $deadline) {
                proc_terminate($process);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);
                $this->fail(sprintf('Subprocess for %s timed out after %ds. stderr so far: %s', $fixturePath, $timeoutSeconds, $stderr));
            }

            usleep(10000);
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return [
            'stdout' => $stdout,
            'stderr' => $stderr,
            'exitCode' => $exitCode,
        ];
    }
}
