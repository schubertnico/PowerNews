<?php

declare(strict_types=1);

namespace PowerNews\Tests\Unit\Installer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PowerNews\Installer\Schema;
use RuntimeException;
use UnexpectedValueException;

require_once __DIR__ . '/../../../pninc/installer/autoload.php';

/**
 * SQL-Splitter und Prüfung von powernews.sql (Befunde B01 und B18).
 */
final class SchemaTest extends TestCase
{
    private const string SCHEMA_FILE = __DIR__ . '/../../../powernews.sql';

    #[Test]
    public function semicolonsInHtmlEntitiesDoNotSplitStatements(): void
    {
        $sql = "INSERT INTO `pn_templates` VALUES (1, '<p>A&nbsp;B &middot; C &raquo; Gr&uuml;&szlig;e</p>');\n"
            . 'INSERT INTO `pn_templates` VALUES (2, \'x\');';

        $statements = Schema::split($sql);

        $this->assertCount(2, $statements);
        $this->assertStringContainsString('A&nbsp;B &middot; C &raquo; Gr&uuml;&szlig;e', $statements[0]);
    }

    #[Test]
    public function escapedAndDoubledQuotesStayInsideTheString(): void
    {
        $sql = "INSERT INTO t VALUES ('It\\'s; fine', 'Say \"hi\"; ok', 'double '' quote; here');\n"
            . 'INSERT INTO t VALUES ("dq \\" ; x");'
            . 'INSERT INTO t VALUES (\'backslash at end \\\\\');';

        $statements = Schema::split($sql);

        $this->assertCount(3, $statements);
        $this->assertStringContainsString("It\\'s; fine", $statements[0]);
        $this->assertStringContainsString("double '' quote; here", $statements[0]);
        $this->assertStringContainsString('dq \\" ; x', $statements[1]);
        $this->assertStringEndsWith("\\\\')", $statements[2]);
    }

    #[Test]
    public function commentsAreRemovedAndMayContainSemicolons(): void
    {
        $sql = "# Kopf; mit Semikolon\n"
            . "-- Zeilenkommentar; auch hier\n"
            . "/* Block; kommentar */ CREATE TABLE `pn_a` (id int);\n"
            . "INSERT INTO `pn_a` VALUES (1); # dahinter; noch einer\n"
            . "SELECT 1--2;\n";

        $statements = Schema::split($sql);

        $this->assertSame(['CREATE TABLE `pn_a` (id int)', 'INSERT INTO `pn_a` VALUES (1)', 'SELECT 1--2'], $statements);
    }

    #[Test]
    public function commentMarkersInsideStringsAreKept(): void
    {
        $statements = Schema::split("INSERT INTO t VALUES ('# kein Kommentar', '-- auch nicht', '/* nein */');");

        $this->assertCount(1, $statements);
        $this->assertStringContainsString("'# kein Kommentar', '-- auch nicht', '/* nein */'", $statements[0]);
    }

    #[Test]
    public function backticksProtectSemicolonsInIdentifiers(): void
    {
        $statements = Schema::split('CREATE TABLE `odd;name` (id int); SELECT 1');

        $this->assertSame(['CREATE TABLE `odd;name` (id int)', 'SELECT 1'], $statements);
    }

    #[Test]
    public function byteOrderMarkAndCrlfLineEndingsAreHandled(): void
    {
        $statements = Schema::split("\xEF\xBB\xBF# Kommentar\r\nCREATE TABLE `pn_a` (\r\n  id int\r\n);\r\nINSERT INTO `pn_a` VALUES (1);\r\n");

        $this->assertCount(2, $statements);
        $this->assertStringStartsWith('CREATE TABLE', $statements[0]);
    }

    #[Test]
    public function emptyInputAndOnlyCommentsYieldNoStatements(): void
    {
        $this->assertSame([], Schema::split(''));
        $this->assertSame([], Schema::split("# a\n-- b\n/* c */\n;;\n"));
    }

    #[Test]
    public function unterminatedStringDoesNotLoop(): void
    {
        $statements = Schema::split("INSERT INTO t VALUES ('offen; ohne Ende");

        $this->assertCount(1, $statements);
    }

    #[Test]
    public function releaseSchemaContainsAllTablesAndOnlyTheDefaultTemplate(): void
    {
        $statements = Schema::fromFile(self::SCHEMA_FILE);

        $this->assertSame(
            ['pn_categories', 'pn_comments', 'pn_config', 'pn_news', 'pn_permissions', 'pn_sessions', 'pn_login_attempts', 'pn_templates', 'pn_users'],
            Schema::tableNames($statements),
        );

        $inserts = array_values(array_filter($statements, static fn (string $s): bool => Schema::insertedTable($s) !== null));
        $this->assertSame(['pn_categories', 'pn_config', 'pn_templates'], array_map(Schema::insertedTable(...), $inserts));

        $templates = array_values(array_filter($inserts, static fn (string $s): bool => Schema::insertedTable($s) === 'pn_templates'));
        $this->assertCount(1, $templates, 'Nur das Standard-Template „Default“ (B18)');
        $this->assertStringContainsString("(1,'Default',", $templates[0]);
        $this->assertStringContainsString('&middot;', $templates[0], 'Semikolons der HTML-Entitäten bleiben erhalten (B01)');

        foreach (['ftghgf', 'dsfs', 'dfgdfg'] as $junk) {
            $this->assertStringNotContainsString("'" . $junk . "'", implode("\n", $statements));
        }
    }

    #[Test]
    public function releaseSchemaIsInstallableAndHasNoDangerousStatements(): void
    {
        $sql = (string) file_get_contents(self::SCHEMA_FILE);
        $statements = Schema::fromFile(self::SCHEMA_FILE);

        Schema::assertInstallable($statements);

        $this->assertDoesNotMatchRegularExpression('/^\s*DROP\s/mi', $sql, 'Kein DROP TABLE im Release-Schema');
        $this->assertStringNotContainsString('INSERT INTO `pn_users`', $sql, 'Kein Standard-Administrator');
        $this->assertStringNotContainsString('INSERT INTO `pn_permissions`', $sql, 'Keine verwaiste Rechtezeile');
        $this->assertStringNotContainsString('powerscripts.org', $sql, 'Neutrale Vorgaben für URL und Absender');
        $this->assertStringNotContainsString("\u{FFFD}", $sql, 'Kommentare sauber in UTF-8');
        $this->assertTrue(mb_check_encoding($sql, 'UTF-8'));
    }

    #[Test]
    public function everyTableUsesUtf8mb4(): void
    {
        foreach (Schema::fromFile(self::SCHEMA_FILE) as $statement) {
            if (Schema::createdTable($statement) !== null) {
                $this->assertStringContainsString('DEFAULT CHARSET=utf8mb4', $statement);
            }
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function forbiddenStatements(): iterable
    {
        yield 'DROP TABLE' => ['DROP TABLE IF EXISTS `pn_users`'];
        yield 'ALTER TABLE' => ['ALTER TABLE pn_config ADD x int'];
        yield 'DELETE' => ['DELETE FROM pn_users'];
        yield 'fremde Tabelle' => ['CREATE TABLE `wp_users` (id int)'];
        yield 'INSERT fremde Tabelle' => ['INSERT INTO users VALUES (1)'];
        yield 'SET anderes' => ['SET FOREIGN_KEY_CHECKS = 0'];
        yield 'UPDATE' => ["UPDATE pn_config SET url = 'x'"];
    }

    #[Test]
    #[DataProvider('forbiddenStatements')]
    public function unexpectedStatementsAreRejected(string $statement): void
    {
        $this->assertFalse(Schema::isAllowed($statement));

        $this->expectException(UnexpectedValueException::class);
        Schema::assertInstallable(['CREATE TABLE `pn_a` (id int)', $statement]);
    }

    #[Test]
    public function schemaWithoutTablesOrWithDuplicatesIsRejected(): void
    {
        try {
            Schema::assertInstallable(['SET NAMES utf8mb4']);
            $this->fail('Schema ohne Tabellen muss abgelehnt werden.');
        } catch (UnexpectedValueException $e) {
            $this->assertStringContainsString('keine Tabellen', $e->getMessage());
        }

        $this->expectException(UnexpectedValueException::class);
        Schema::assertInstallable(['CREATE TABLE `pn_a` (id int)', 'CREATE TABLE pn_a (id int)']);
    }

    #[Test]
    public function tableNamesAreDetected(): void
    {
        $this->assertSame('pn_users', Schema::createdTable("CREATE TABLE `pn_users` (\n id int)"));
        $this->assertSame('pn_x', Schema::createdTable('create table if not exists pn_x (id int)'));
        $this->assertNull(Schema::createdTable('INSERT INTO pn_x VALUES (1)'));
        $this->assertSame('pn_config', Schema::insertedTable('INSERT INTO `pn_config` VALUES (1)'));
        $this->assertNull(Schema::insertedTable('SET NAMES utf8mb4'));
    }

    #[Test]
    public function missingFileThrowsReadableError(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Die Schemadatei gibt-es-nicht.sql fehlt');

        Schema::fromFile(sys_get_temp_dir() . '/gibt-es-nicht.sql');
    }
}
