<?php
declare(strict_types=1);

namespace PowerNews\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HelperFunctionsTest extends TestCase
{
    private string $tempDir;
    private \pn_user $pnUser;
    private \user $adminUser;
    private \pn_template $template;
    private \menus $menus;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir();
        $this->pnUser = new \pn_user();
        $this->adminUser = new \user();
        $this->template = new \pn_template();
        $this->menus = new \menus();
    }

    // ===========================
    // pn_escape Tests
    // ===========================

    #[Test]
    public function pnEscapeEscapesHtmlSpecialChars(): void
    {
        $this->assertSame('&lt;script&gt;', pn_escape('<script>'));
    }

    #[Test]
    public function pnEscapeEscapesAmpersand(): void
    {
        $this->assertSame('&amp;', pn_escape('&'));
    }

    #[Test]
    public function pnEscapeEscapesDoubleQuotes(): void
    {
        $this->assertSame('&quot;', pn_escape('"'));
    }

    #[Test]
    public function pnEscapeHandlesEmptyString(): void
    {
        $this->assertSame('', pn_escape(''));
    }

    #[Test]
    public function pnEscapeHandlesInteger(): void
    {
        $result = pn_escape(42);
        $this->assertIsString($result);
        $this->assertSame('42', $result);
    }

    #[Test]
    public function pnEscapeHandlesFloat(): void
    {
        $result = pn_escape(3.14);
        $this->assertIsString($result);
        $this->assertStringContainsString('3.14', $result);
    }

    #[Test]
    public function pnEscapeHandlesNull(): void
    {
        $result = pn_escape(null);
        $this->assertIsString($result);
        $this->assertSame('', $result);
    }

    #[Test]
    public function pnEscapeHandlesPlainText(): void
    {
        $this->assertSame('Hello World', pn_escape('Hello World'));
    }

    #[Test]
    public function pnEscapeHandlesMixedContent(): void
    {
        $result = pn_escape('<b>Bold & "quoted"</b>');
        $this->assertStringNotContainsString('<b>', $result);
        $this->assertStringContainsString('&lt;b&gt;', $result);
        $this->assertStringContainsString('&amp;', $result);
        $this->assertStringContainsString('&quot;', $result);
    }

    // ===========================
    // pn_user::generate_password Tests
    // ===========================

    #[Test]
    public function pnUserGeneratePasswordReturns8Chars(): void
    {
        $password = $this->pnUser->generate_password();
        $this->assertSame(8, strlen($password));
    }

    #[Test]
    public function pnUserGeneratePasswordContainsOnlyAlphanumeric(): void
    {
        $password = $this->pnUser->generate_password();
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]{8}$/', $password);
    }

    #[Test]
    public function pnUserGeneratePasswordProducesDifferentResults(): void
    {
        $passwords = [];
        for ($i = 0; $i < 10; $i++) {
            $passwords[] = $this->pnUser->generate_password();
        }
        $unique = array_unique($passwords);
        $this->assertGreaterThan(1, count($unique));
    }

    #[Test]
    public function pnUserGeneratePasswordReturnsString(): void
    {
        $password = $this->pnUser->generate_password();
        $this->assertIsString($password);
    }

    // ===========================
    // user::generate_password Tests (admin version)
    // ===========================

    #[Test]
    public function adminUserGeneratePasswordReturns8Chars(): void
    {
        $password = $this->adminUser->generate_password();
        $this->assertSame(8, strlen($password));
    }

    #[Test]
    public function adminUserGeneratePasswordContainsOnlyAlphanumeric(): void
    {
        $password = $this->adminUser->generate_password();
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]{8}$/', $password);
    }

    #[Test]
    public function adminUserGeneratePasswordProducesDifferentResults(): void
    {
        $passwords = [];
        for ($i = 0; $i < 10; $i++) {
            $passwords[] = $this->adminUser->generate_password();
        }
        $unique = array_unique($passwords);
        $this->assertGreaterThan(1, count($unique));
    }

    #[Test]
    public function adminUserGeneratePasswordReturnsString(): void
    {
        $password = $this->adminUser->generate_password();
        $this->assertIsString($password);
    }

    // ===========================
    // pnadmin_escape Tests
    // ===========================

    #[Test]
    public function pnadminEscapeEscapesHtmlSpecialChars(): void
    {
        $this->assertSame('&lt;script&gt;', pnadmin_escape('<script>'));
    }

    #[Test]
    public function pnadminEscapeEscapesAmpersand(): void
    {
        $this->assertSame('&amp;', pnadmin_escape('&'));
    }

    #[Test]
    public function pnadminEscapeEscapesDoubleQuotes(): void
    {
        $this->assertSame('&quot;', pnadmin_escape('"'));
    }

    #[Test]
    public function pnadminEscapeHandlesEmptyString(): void
    {
        $this->assertSame('', pnadmin_escape(''));
    }

    #[Test]
    public function pnadminEscapeHandlesPlainText(): void
    {
        $this->assertSame('Hello World', pnadmin_escape('Hello World'));
    }

    #[Test]
    public function pnadminEscapeHandlesAngleBrackets(): void
    {
        $result = pnadmin_escape('<div>test</div>');
        $this->assertStringNotContainsString('<div>', $result);
        $this->assertStringContainsString('&lt;div&gt;', $result);
    }

    // ===========================
    // readDump Tests
    // ===========================

    #[Test]
    public function readDumpParsesSimpleSqlFile(): void
    {
        $file = $this->tempDir . '/test_dump_simple.sql';
        file_put_contents($file, "CREATE TABLE test (id INT);\nINSERT INTO test VALUES (1);");

        $result = readDump($file);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(2, count($result));
        unlink($file);
    }

    #[Test]
    public function readDumpStripsHashComments(): void
    {
        $file = $this->tempDir . '/test_dump_comments.sql';
        file_put_contents($file, "# This is a comment\nCREATE TABLE test (id INT);\n# Another comment\nINSERT INTO test VALUES (1);");

        $result = readDump($file);

        foreach ($result as $sql) {
            $this->assertStringNotContainsString('# This is a comment', $sql);
            $this->assertStringNotContainsString('# Another comment', $sql);
        }
        unlink($file);
    }

    #[Test]
    public function readDumpSplitsBySemicolon(): void
    {
        $file = $this->tempDir . '/test_dump_split.sql';
        file_put_contents($file, "SELECT 1;\nSELECT 2;\nSELECT 3;");

        $result = readDump($file);

        $this->assertGreaterThanOrEqual(3, count($result));
        unlink($file);
    }

    #[Test]
    public function readDumpHandlesEmptyFile(): void
    {
        $file = $this->tempDir . '/test_dump_empty.sql';
        file_put_contents($file, '');

        $result = readDump($file);

        $this->assertIsArray($result);
        unlink($file);
    }

    #[Test]
    public function readDumpHandlesFileWithOnlyComments(): void
    {
        $file = $this->tempDir . '/test_dump_only_comments.sql';
        file_put_contents($file, "# Comment 1\n# Comment 2\n# Comment 3\n");

        $result = readDump($file);

        $this->assertIsArray($result);
        $nonEmpty = array_filter($result, fn($s) => trim($s) !== '');
        $this->assertCount(0, $nonEmpty);
        unlink($file);
    }

    #[Test]
    public function readDumpHandlesMultilineStatements(): void
    {
        $file = $this->tempDir . '/test_dump_multiline.sql';
        file_put_contents($file, "CREATE TABLE test (\n  id INT,\n  name VARCHAR(255)\n);\nINSERT INTO test VALUES (1, 'test');");

        $result = readDump($file);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(2, count($result));
        unlink($file);
    }

    #[Test]
    public function readDumpHandlesMixedCommentsAndStatements(): void
    {
        $file = $this->tempDir . '/test_dump_mixed.sql';
        file_put_contents($file, "# Header comment\n# Version 1.0\nCREATE TABLE t1 (id INT);\n# Separator\nINSERT INTO t1 VALUES (1);\nINSERT INTO t1 VALUES (2);");

        $result = readDump($file);

        $this->assertIsArray($result);
        $nonEmpty = array_filter($result, fn($s) => trim($s) !== '');
        $this->assertGreaterThanOrEqual(3, count($nonEmpty));
        unlink($file);
    }

    // ===========================
    // pn_cpi Tests
    // ===========================

    #[Test]
    public function pnCpiProducesNoOutput(): void
    {
        global $pn_config;
        $pn_config['version'] = '1.0.0';

        // Seit Mai 2026 ist pn_cpi() ein No-Op. Die frueher gerendete Copyright-
        // Zeile direkt unter dem News-Inhalt hat sich mit dem neuen globalen
        // Bootstrap-5-Footer in footer.inc.php gedoppelt. Funktion bleibt fuer
        // API-Kompatibilitaet, gibt aber bewusst nichts mehr aus.
        ob_start();
        pn_cpi();
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    #[Test]
    public function pnCpiReturnsVoid(): void
    {
        global $pn_config;
        $pn_config['version'] = '2.0.0';

        // pn_cpi() ist als void deklariert und liefert keinen Rueckgabewert.
        $result = pn_cpi();

        $this->assertNull($result);
    }

    // ===========================
    // pn_template::bbreplace Tests
    // ===========================

    #[Test]
    public function bbreplaceConvertsBoldTag(): void
    {
        $result = $this->template->bbreplace('[b]bold text[/b]');
        $this->assertStringContainsString('<b>bold text</b>', $result);
    }

    #[Test]
    public function bbreplaceConvertsUnderlineTag(): void
    {
        $result = $this->template->bbreplace('[u]underline text[/u]');
        $this->assertStringContainsString('<u>underline text</u>', $result);
    }

    #[Test]
    public function bbreplaceConvertsItalicTag(): void
    {
        $result = $this->template->bbreplace('[i]italic text[/i]');
        $this->assertStringContainsString('<i>italic text</i>', $result);
    }

    #[Test]
    public function bbreplaceConvertsUrlWithHttp(): void
    {
        $result = $this->template->bbreplace('[url]http://example.com[/url]');
        $this->assertStringContainsString('href="http://example.com"', $result);
        $this->assertStringContainsString('target="_blank"', $result);
        $this->assertStringContainsString('>http://example.com</a>', $result);
    }

    #[Test]
    public function bbreplaceConvertsUrlWithoutHttp(): void
    {
        $result = $this->template->bbreplace('[url]example.com[/url]');
        $this->assertStringContainsString('href="http://example.com"', $result);
        $this->assertStringContainsString('>example.com</a>', $result);
    }

    #[Test]
    public function bbreplaceConvertsEmailTag(): void
    {
        $result = $this->template->bbreplace('[email]test@example.com[/email]');
        $this->assertStringContainsString('href="mailto:test@example.com"', $result);
        $this->assertStringContainsString('>test@example.com</a>', $result);
    }

    #[Test]
    public function bbreplaceConvertsImgTag(): void
    {
        // After BUG-048 fix, [img] only converts for whitelisted hosts (own host / localhost).
        // External hosts remain as plain text (escaped) to prevent tracking pixels.
        global $pnconfig;
        $pnconfig['url'] = 'http://example.com';

        $result = $this->template->bbreplace('[img]http://example.com/img.jpg[/img]');
        $this->assertStringContainsString('<img', $result);
        $this->assertStringContainsString('src="http://example.com/img.jpg"', $result);
        $this->assertStringContainsString('border="0"', $result);

        // External host NOT converted
        $result2 = $this->template->bbreplace('[img]http://evil.example.org/track[/img]');
        $this->assertStringNotContainsString('<img src="http://evil.example.org', $result2);
        $this->assertStringContainsString('[img]', $result2);
    }

    #[Test]
    public function bbreplaceConvertsNewlinesToBr(): void
    {
        $result = $this->template->bbreplace("line1\nline2");
        $this->assertStringContainsString('<br>', $result);
    }

    #[Test]
    public function bbreplaceHandlesMultipleTags(): void
    {
        $result = $this->template->bbreplace('[b]bold[/b] and [i]italic[/i]');
        $this->assertStringContainsString('<b>bold</b>', $result);
        $this->assertStringContainsString('<i>italic</i>', $result);
    }

    #[Test]
    public function bbreplaceHandlesNestedTags(): void
    {
        $result = $this->template->bbreplace('[b][i]bold italic[/i][/b]');
        $this->assertStringContainsString('<b><i>bold italic</i></b>', $result);
    }

    #[Test]
    public function bbreplaceHandlesEmptyString(): void
    {
        $result = $this->template->bbreplace('');
        $this->assertSame('', $result);
    }

    #[Test]
    public function bbreplaceHandlesPlainText(): void
    {
        $result = $this->template->bbreplace('no tags here');
        $this->assertSame('no tags here', $result);
    }

    #[Test]
    public function bbreplaceHandlesMultipleUrlTags(): void
    {
        $result = $this->template->bbreplace('[url]http://one.com[/url] [url]http://two.com[/url]');
        $this->assertStringContainsString('href="http://one.com"', $result);
        $this->assertStringContainsString('href="http://two.com"', $result);
    }

    #[Test]
    public function bbreplaceHandlesMultipleNewlines(): void
    {
        $result = $this->template->bbreplace("a\nb\nc");
        $this->assertSame(2, substr_count($result, '<br>'));
    }

    // ===========================
    // pn_template::smiliereplace Tests
    // ===========================

    #[Test]
    public function smiliereplaceConvertsSmile(): void
    {
        $result = $this->template->smiliereplace(':)');
        $this->assertStringContainsString('smile.gif', $result);
    }

    #[Test]
    public function smiliereplaceConvertsLaugh(): void
    {
        $result = $this->template->smiliereplace(':))');
        $this->assertStringContainsString('laugh.gif', $result);
    }

    #[Test]
    public function smiliereplaceConvertsWink(): void
    {
        $result = $this->template->smiliereplace(';)');
        $this->assertStringContainsString('wink.gif', $result);
    }

    #[Test]
    public function smiliereplaceConvertsTongue(): void
    {
        $result = $this->template->smiliereplace(':p');
        $this->assertStringContainsString('tongue.gif', $result);
    }

    #[Test]
    public function smiliereplaceConvertsBigSmile(): void
    {
        $result = $this->template->smiliereplace(':D');
        $this->assertStringContainsString('bigsmile.gif', $result);
    }

    #[Test]
    public function smiliereplaceConvertsSad(): void
    {
        $result = $this->template->smiliereplace(':(');
        $this->assertStringContainsString('sad.gif', $result);
    }

    #[Test]
    public function smiliereplaceConvertsConfused(): void
    {
        $result = $this->template->smiliereplace(':?:');
        $this->assertStringContainsString('confused.gif', $result);
    }

    #[Test]
    public function smiliereplaceHandlesEmptyString(): void
    {
        $result = $this->template->smiliereplace('');
        $this->assertSame('', $result);
    }

    #[Test]
    public function smiliereplaceHandlesPlainText(): void
    {
        $result = $this->template->smiliereplace('no smilies here');
        $this->assertSame('no smilies here', $result);
    }

    #[Test]
    public function smiliereplaceHandlesMultipleSmilies(): void
    {
        $result = $this->template->smiliereplace(':) and :D');
        $this->assertStringContainsString('smile.gif', $result);
        $this->assertStringContainsString('bigsmile.gif', $result);
    }

    #[Test]
    public function smiliereplaceProducesImgTags(): void
    {
        $result = $this->template->smiliereplace(':)');
        $this->assertStringContainsString('<img', $result);
    }

    // ===========================
    // menus::statusmenu Tests
    // ===========================

    #[Test]
    public function statusmenuShowsLoginMessageWhenNotLoggedIn(): void
    {
        ob_start();
        $this->menus->statusmenu('NO', '');
        $output = ob_get_clean();

        // L_USR_PLEASELOGIN = 'Please log in'
        $this->assertStringContainsString('log in', strtolower($output));
    }

    #[Test]
    public function statusmenuShowsUsernameWhenLoggedIn(): void
    {
        ob_start();
        $this->menus->statusmenu('YES', 'admin');
        $output = ob_get_clean();

        $this->assertStringContainsString('admin', $output);
    }

    #[Test]
    public function statusmenuShowsHelloWhenLoggedIn(): void
    {
        ob_start();
        $this->menus->statusmenu('YES', 'testuser');
        $output = ob_get_clean();

        // L_USR_HELLO = 'Hello'
        $this->assertStringContainsString('Hello', $output);
        $this->assertStringContainsString('testuser', $output);
    }

    #[Test]
    public function statusmenuEscapesUsername(): void
    {
        ob_start();
        $this->menus->statusmenu('YES', '<script>alert(1)</script>');
        $output = ob_get_clean();

        $this->assertStringNotContainsString('<script>', $output);
    }

    // ===========================
    // menus::submenu Tests
    // ===========================

    #[Test]
    public function submenuOutputsTemplatesMenu(): void
    {
        ob_start();
        $this->menus->submenu('templates');
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
    }

    #[Test]
    public function submenuOutputsUsersMenu(): void
    {
        ob_start();
        $this->menus->submenu('users');
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
    }

    #[Test]
    public function submenuOutputsPermissionsMenu(): void
    {
        ob_start();
        $this->menus->submenu('permissions');
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
    }

    #[Test]
    public function submenuOutputsConfigurationMenu(): void
    {
        ob_start();
        $this->menus->submenu('configuration');
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
    }

    #[Test]
    public function submenuOutputsCategoriesMenu(): void
    {
        global $pnconfig;
        $pnconfig['categories'] = 'YES';

        ob_start();
        $this->menus->submenu('categories');
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
    }

    #[Test]
    public function submenuOutputsNewsMenu(): void
    {
        ob_start();
        $this->menus->submenu('news');
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
    }

    #[Test]
    public function submenuOutputsDefaultMenu(): void
    {
        ob_start();
        $this->menus->submenu('nonexistent_page');
        $output = ob_get_clean();

        $this->assertIsString($output);
    }

    #[Test]
    public function submenuOutputsDifferentContentForDifferentPages(): void
    {
        ob_start();
        $this->menus->submenu('templates');
        $templatesOutput = ob_get_clean();

        ob_start();
        $this->menus->submenu('users');
        $usersOutput = ob_get_clean();

        $this->assertNotSame($templatesOutput, $usersOutput);
    }
}
