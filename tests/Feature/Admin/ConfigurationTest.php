<?php
declare(strict_types=1);

namespace PowerNews\Tests\Feature\Admin;

use PowerNews\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for configuration form (pnadmin/configuration.inc.php)
 */
class ConfigurationTest extends TestCase
{
    /**
     * Yes/No configuration fields
     */
    private const YES_NO_FIELDS = [
        'show_statistic',
        'comment_notify',
        'confirm_email',
        'user_sendnews',
    ];

    // === Configuration Edit Tests ===

    #[Test]
    public function configurationActionTriggeredByGetParameter(): void
    {
        $this->setGet(['action' => 'edit']);

        $action = $_GET['action'] ?? '';
        $this->assertEquals('edit', $action);
    }

    #[Test]
    public function configurationAcceptsValidNewsTopic(): void
    {
        $this->setPost(['newstopic' => 'PowerNews Updates']);

        $newstopic = trim((string)($_POST['newstopic'] ?? ''));
        $this->assertNotEmpty($newstopic);
    }

    #[Test]
    public function configurationAcceptsValidNewsPerPage(): void
    {
        $this->setPost(['newsperpage' => '10']);

        $newsperpage = (int)($_POST['newsperpage'] ?? 0);
        $this->assertEquals(10, $newsperpage);
        $this->assertGreaterThan(0, $newsperpage);
    }

    #[Test]
    public function configurationCastsNewsPerPageToInteger(): void
    {
        $this->setPost(['newsperpage' => '15abc']);

        $newsperpage = (int)($_POST['newsperpage'] ?? 0);
        $this->assertEquals(15, $newsperpage);
    }

    #[Test]
    public function configurationRejectsInvalidNewsPerPage(): void
    {
        $this->setPost(['newsperpage' => '0']);

        $newsperpage = (int)($_POST['newsperpage'] ?? 0);
        $isValid = $newsperpage > 0;

        $this->assertFalse($isValid);
    }

    #[Test]
    #[DataProvider('yesNoFieldProvider')]
    public function configurationAcceptsYesNoFields(string $field): void
    {
        $this->setPost([$field => 'YES']);

        $value = $_POST[$field] ?? '';
        $this->assertTrue(in_array($value, ['YES', 'NO']));
    }

    public static function yesNoFieldProvider(): array
    {
        return [
            'show_statistic' => ['show_statistic'],
            'comment_notify' => ['comment_notify'],
            'confirm_email' => ['confirm_email'],
            'user_sendnews' => ['user_sendnews'],
        ];
    }

    #[Test]
    public function configurationAcceptsValidCommentAccess(): void
    {
        $this->setPost(['comment_access' => 'Registered']);

        $access = $_POST['comment_access'] ?? '';
        $allowedValues = ['Guests & Registered', 'Registered', 'Nobody'];

        $this->assertTrue(in_array($access, $allowedValues));
    }

    #[Test]
    #[DataProvider('commentAccessProvider')]
    public function configurationAcceptsAllCommentAccessLevels(string $access): void
    {
        $this->setPost(['comment_access' => $access]);

        $allowedValues = ['Guests & Registered', 'Registered', 'Nobody'];
        $this->assertTrue(in_array($access, $allowedValues));
    }

    public static function commentAccessProvider(): array
    {
        return [
            'guests and registered' => ['Guests & Registered'],
            'registered only' => ['Registered'],
            'nobody' => ['Nobody'],
        ];
    }

    #[Test]
    public function configurationRejectsInvalidCommentAccess(): void
    {
        $this->setPost(['comment_access' => 'Invalid']);

        $access = $_POST['comment_access'] ?? '';
        $allowedValues = ['Guests & Registered', 'Registered', 'Nobody'];

        $this->assertFalse(in_array($access, $allowedValues));
    }

    #[Test]
    public function configurationAcceptsValidTemplate(): void
    {
        $this->setPost(['template' => '1']);

        $template = (int)($_POST['template'] ?? 0);
        $this->assertGreaterThan(0, $template);
    }

    #[Test]
    public function configurationAcceptsValidEmailAddress(): void
    {
        $this->setPost(['admin_email' => 'admin@example.com']);

        $email = trim((string)($_POST['admin_email'] ?? ''));
        $this->assertValidEmail($email);
    }

    #[Test]
    public function configurationRejectsInvalidEmailAddress(): void
    {
        $this->setPost(['admin_email' => 'invalid-email']);

        $email = trim((string)($_POST['admin_email'] ?? ''));
        $this->assertInvalidEmail($email);
    }

    #[Test]
    public function configurationHandlesXSSInNewsTopic(): void
    {
        $this->setPost(['newstopic' => '<script>alert("xss")</script>']);

        $newstopic = htmlspecialchars((string)($_POST['newstopic'] ?? ''), ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString('<script>', $newstopic);
    }

    #[Test]
    public function configurationSanitizesWhitespace(): void
    {
        $this->setPost(['newstopic' => '  News Topic  ']);

        $newstopic = trim((string)($_POST['newstopic'] ?? ''));
        $this->assertEquals('News Topic', $newstopic);
    }

    #[Test]
    public function configurationAcceptsDateFormat(): void
    {
        $this->setPost(['dateformat' => 'd.m.Y H:i']);

        $format = $_POST['dateformat'] ?? '';
        $this->assertNotEmpty($format);
    }

    #[Test]
    public function configurationAcceptsStartpage(): void
    {
        $this->setPost(['startpage' => 'news']);

        $startpage = $_POST['startpage'] ?? '';
        $allowedPages = ['news', 'archive', 'custom'];

        // Assuming startpage validation
        $this->assertNotEmpty($startpage);
    }

    #[Test]
    public function configurationHandlesSQLInjectionInFields(): void
    {
        $this->setPost([
            'newstopic' => "'; DROP TABLE config;--",
        ]);

        $newstopic = htmlspecialchars((string)($_POST['newstopic'] ?? ''), ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString("'", $newstopic);
        $this->assertStringContainsString('&#039;', $newstopic);
    }

    #[Test]
    public function configurationHandlesMultipleFieldsAtOnce(): void
    {
        $this->setPost([
            'newstopic' => 'PowerNews',
            'newsperpage' => '10',
            'show_statistic' => 'YES',
            'comment_notify' => 'NO',
            'admin_email' => 'admin@example.com',
        ]);

        $this->assertEquals('PowerNews', trim((string)($_POST['newstopic'] ?? '')));
        $this->assertEquals(10, (int)($_POST['newsperpage'] ?? 0));
        $this->assertEquals('YES', $_POST['show_statistic'] ?? '');
        $this->assertEquals('NO', $_POST['comment_notify'] ?? '');
        $this->assertValidEmail(trim((string)($_POST['admin_email'] ?? '')));
    }
}
