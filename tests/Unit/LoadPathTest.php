<?php

declare(strict_types=1);

namespace PowerNews\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Jeder Einstieg darf nur Funktionen und Klassen benutzen, die sein eigener Ladeweg
 * definiert. tests/bootstrap.php lädt Frontend und Adminbereich gemeinsam; deshalb fiel
 * in 3.12 nicht auf, dass pnadmin_comment_text() pn_query_by_id() aufrief, das nur in
 * pninc/functions.inc.php stand. „Kommentare editieren“ endete im Adminbereich mit
 * HTTP 500. Geprüft wird in einem eigenen PHP-Prozess (tests/Helpers/load-path-check.php).
 */
final class LoadPathTest extends TestCase
{
    /**
     * @return array{checked: int, findings: list<array{file: string, line: int, kind: string, name: string, declaredIn: ?string}>}
     */
    private function check(string $chain, string $extraFile = ''): array
    {
        // Argumente als Liste, ohne Shell (wie in LocalizedDateTest)
        $command = [PHP_BINARY, __DIR__ . '/../Helpers/load-path-check.php', $chain];
        if ($extraFile !== '') {
            $command[] = $extraFile;
        }

        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $this->assertIsResource($process);
        $output = (string) stream_get_contents($pipes[1]);
        $errors = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $this->assertSame(0, proc_close($process), $errors . $output);

        $result = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('findings', $result);

        /** @var array{checked: int, findings: list<array{file: string, line: int, kind: string, name: string, declaredIn: ?string}>} $result */
        return $result;
    }

    /**
     * @param list<array{file: string, line: int, kind: string, name: string, declaredIn: ?string}> $findings
     */
    private static function describe(array $findings): string
    {
        $lines = array_map(
            static fn (array $f): string => sprintf(
                '%s %s in %s:%d – %s',
                $f['kind'],
                $f['kind'] === 'Funktion' ? $f['name'] . '()' : $f['name'],
                $f['file'],
                $f['line'],
                $f['declaredIn'] !== null ? 'definiert nur in ' . $f['declaredIn'] : 'nirgends definiert',
            ),
            $findings,
        );

        return "Im Ladeweg nicht definiert:\n" . implode("\n", $lines);
    }

    /**
     * @return array<string, array{string, int}> Einstieg und Mindestzahl geprüfter Dateien
     */
    public static function chains(): array
    {
        return [
            'Adminbereich (pnadmin/index.php)' => ['admin', 40],
            'Update (update.php)' => ['update', 15],
            'Frontend (index.php, news.php, …)' => ['frontend', 20],
        ];
    }

    #[Test]
    #[DataProvider('chains')]
    public function every_called_function_is_defined_on_the_load_path(string $chain, int $minimumFiles): void
    {
        $result = $this->check($chain);

        $this->assertSame([], $result['findings'], self::describe($result['findings']));
        $this->assertGreaterThanOrEqual($minimumFiles, $result['checked']);
    }

    #[Test]
    public function the_check_reports_frontend_functions_used_in_the_admin_area(): void
    {
        $result = $this->check('admin', __DIR__ . '/../Fixtures/load-path-admin-page.php');

        $reported = array_map(
            static fn (array $f): string => $f['kind'] . ' ' . $f['name'] . ' (' . $f['declaredIn'] . ')',
            array_values(array_filter($result['findings'], static fn (array $f): bool => str_ends_with($f['file'], 'load-path-admin-page.php'))),
        );

        $this->assertSame([
            'Funktion pn_format_date (pninc/functions.inc.php)',
            'Klasse pn_template (pninc/functions.inc.php)',
        ], $reported);
    }
}
