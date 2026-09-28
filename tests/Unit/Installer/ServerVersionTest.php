<?php

declare(strict_types=1);

namespace PowerNews\Tests\Unit\Installer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PowerNews\Installer\ServerVersion;

require_once __DIR__ . '/../../../pninc/installer/autoload.php';

/**
 * MySQL und MariaDB werden getrennt bewertet (Befund B27).
 */
final class ServerVersionTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, string, bool}>
     */
    public static function versions(): iterable
    {
        yield 'MariaDB 10.11 (Docker)' => ['10.11.15-MariaDB-ubu2204', ServerVersion::MARIADB, '10.11.15', true];
        yield 'MariaDB 10.3 (Minimum)' => ['10.3.39-MariaDB-0+deb10u2', ServerVersion::MARIADB, '10.3.39', true];
        yield 'MariaDB 10.3.0 exakt' => ['10.3.0-MariaDB', ServerVersion::MARIADB, '10.3.0', true];
        yield 'MariaDB mit 5.5.5-Präfix' => ['5.5.5-10.6.18-MariaDB-log', ServerVersion::MARIADB, '10.6.18', true];
        yield 'MariaDB 11.4' => ['11.4.2-MariaDB', ServerVersion::MARIADB, '11.4.2', true];
        yield 'MariaDB 10.2 zu alt' => ['10.2.44-MariaDB', ServerVersion::MARIADB, '10.2.44', false];
        yield 'MariaDB 10.2 mit Präfix zu alt' => ['5.5.5-10.2.44-MariaDB-log', ServerVersion::MARIADB, '10.2.44', false];
        yield 'MySQL 8.0' => ['8.0.39', ServerVersion::MYSQL, '8.0.39', true];
        yield 'MySQL 8.0.0 exakt' => ['8.0.0', ServerVersion::MYSQL, '8.0.0', true];
        yield 'MySQL 8.4 LTS' => ['8.4.2', ServerVersion::MYSQL, '8.4.2', true];
        yield 'MySQL 9' => ['9.0.1-commercial', ServerVersion::MYSQL, '9.0.1', true];
        yield 'Percona Server 8.0' => ['8.0.36-28', ServerVersion::MYSQL, '8.0.36', true];
        yield 'MySQL 5.7 zu alt' => ['5.7.44-log', ServerVersion::MYSQL, '5.7.44', false];
        yield 'MySQL 5.5 zu alt' => ['5.5.62', ServerVersion::MYSQL, '5.5.62', false];
        yield 'nur Major.Minor' => ['8.0', ServerVersion::MYSQL, '8.0.0', true];
    }

    #[Test]
    #[DataProvider('versions')]
    public function recognisesServerTypeAndMinimum(string $raw, string $type, string $version, bool $supported): void
    {
        $server = ServerVersion::parse($raw);

        $this->assertNotNull($server);
        $this->assertSame($type, $server->type);
        $this->assertSame($version, $server->version);
        $this->assertSame($supported, $server->isSupported());
        $this->assertSame($type . ' ' . $version, $server->label());
    }

    #[Test]
    public function mysql8IsNotComparedWithTheMariaDbNumber(): void
    {
        $mysql = ServerVersion::parse('8.0.39');
        $this->assertNotNull($mysql);
        $this->assertSame(ServerVersion::MIN_MYSQL, $mysql->minimum());
        $this->assertTrue($mysql->isSupported(), '3.11 hat MySQL 8 fälschlich mit „10.3“ verglichen');

        $mariadb = ServerVersion::parse('10.11.15-MariaDB');
        $this->assertNotNull($mariadb);
        $this->assertSame(ServerVersion::MIN_MARIADB, $mariadb->minimum());
    }

    #[Test]
    public function unknownVersionStringYieldsNull(): void
    {
        $this->assertNull(ServerVersion::parse(''));
        $this->assertNull(ServerVersion::parse('MariaDB'));
        $this->assertNull(ServerVersion::parse('unbekannt'));
    }

    #[Test]
    public function requirementTextNamesBothServers(): void
    {
        $this->assertSame('MySQL 8.0 oder neuer bzw. MariaDB 10.3 oder neuer', ServerVersion::requirement());
    }
}
