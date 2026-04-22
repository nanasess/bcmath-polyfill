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
    #[RequiresPhp('< 8.4')]
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
     * @return array{stdout: string, stderr: string, exitCode: int}
     */
    private function runFixture(string $fixturePath): array
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
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [
            'stdout' => $stdout === false ? '' : $stdout,
            'stderr' => $stderr === false ? '' : $stderr,
            'exitCode' => $exitCode,
        ];
    }
}
