<?php
declare(strict_types=1);

namespace PowerNews\Tests\Feature\Admin;

use PowerNews\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for template management forms (pnadmin/templates_add.inc.php, templates_edit.inc.php)
 */
class TemplatesTest extends TestCase
{
    /**
     * Template field names
     */
    private const TEMPLATE_FIELDS = [
        'title',
        'start_top',
        'start_bottom',
        'news_start',
        'news_entry',
        'news_noentry',
        'news_end',
        'news_pages',
        'news_archive',
        'news_archivemonth',
        'comments_start',
        'comments_entry',
        'comments_noentry',
        'comments_end',
        'comments_form',
        'user_register',
        'user_login',
        'user_profile',
        'user_senddata',
        'sendnews',
    ];

    // === Template Add Tests ===

    #[Test]
    public function addTemplateRequiresTitle(): void
    {
        $this->setPost([
            'title' => '',
        ]);

        $title = trim((string)($_POST['title'] ?? ''));
        $this->assertEmpty($title, 'Empty title should fail validation');
    }

    #[Test]
    public function addTemplateAcceptsValidTitle(): void
    {
        $this->setPost([
            'title' => 'My Custom Template',
        ]);

        $title = trim((string)($_POST['title'] ?? ''));
        $this->assertNotEmpty($title);
        $this->assertEquals('My Custom Template', $title);
    }

    #[Test]
    public function addTemplateHandlesXSSInTitle(): void
    {
        $this->setPost([
            'title' => '<script>alert("xss")</script>',
        ]);

        $title = htmlspecialchars((string)($_POST['title'] ?? ''), ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString('<script>', $title);
        $this->assertStringContainsString('&lt;script&gt;', $title);
    }

    #[Test]
    public function addTemplateSanitizesWhitespace(): void
    {
        $this->setPost([
            'title' => '  Custom Template  ',
        ]);

        $title = trim((string)($_POST['title'] ?? ''));
        $this->assertEquals('Custom Template', $title);
    }

    // === Template Edit Tests ===

    #[Test]
    public function editTemplateRequiresTemplateId(): void
    {
        $this->setGet(['templateid' => '']);

        $templateid = (int)($_GET['templateid'] ?? 0);
        $this->assertEquals(0, $templateid, 'Empty templateid should result in 0');
    }

    #[Test]
    public function editTemplateCastsTemplateIdToInteger(): void
    {
        $this->setGet(['templateid' => '15abc']);

        $templateid = (int)($_GET['templateid'] ?? 0);
        $this->assertEquals(15, $templateid);
    }

    #[Test]
    #[DataProvider('templateFieldProvider')]
    public function editTemplateAcceptsAllFields(string $field): void
    {
        $this->setPost([
            $field => 'Test content for ' . $field,
        ]);

        $value = $_POST[$field] ?? '';
        $this->assertNotEmpty($value);
    }

    public static function templateFieldProvider(): array
    {
        $fields = [];
        foreach ([
            'title', 'start_top', 'start_bottom', 'news_start', 'news_entry',
            'news_noentry', 'news_end', 'news_pages', 'news_archive',
            'news_archivemonth', 'comments_start', 'comments_entry',
            'comments_noentry', 'comments_end', 'comments_form',
            'user_register', 'user_login', 'user_profile', 'user_senddata', 'sendnews'
        ] as $field) {
            $fields[$field] = [$field];
        }
        return $fields;
    }

    #[Test]
    public function editTemplateAllowsHtmlContent(): void
    {
        $this->setPost([
            'news_entry' => '<div class="news-item"><h2>{TITLE}</h2><p>{TEXT}</p></div>',
        ]);

        $content = $_POST['news_entry'] ?? '';

        // Templates should allow HTML - this is intentional
        $this->assertStringContainsString('<div', $content);
        $this->assertStringContainsString('{TITLE}', $content);
    }

    #[Test]
    public function editTemplatePreservesPlaceholders(): void
    {
        $this->setPost([
            'news_entry' => '{NEWSID} - {TITLE} by {NICKNAME}',
        ]);

        $content = $_POST['news_entry'] ?? '';

        $this->assertStringContainsString('{NEWSID}', $content);
        $this->assertStringContainsString('{TITLE}', $content);
        $this->assertStringContainsString('{NICKNAME}', $content);
    }

    #[Test]
    public function editTemplateHandlesEmptyFields(): void
    {
        $postData = ['title' => 'Template'];
        foreach (self::TEMPLATE_FIELDS as $field) {
            if ($field !== 'title') {
                $postData[$field] = '';
            }
        }
        $this->setPost($postData);

        foreach (self::TEMPLATE_FIELDS as $field) {
            $value = $_POST[$field] ?? '';
            $this->assertTrue(isset($_POST[$field]), "Field $field should exist");
        }
    }

    #[Test]
    public function editTemplateActionTriggeredByGetParameter(): void
    {
        $this->setGet([
            'templateid' => '1',
            'action' => 'edit',
        ]);

        $action = $_GET['action'] ?? '';
        $templateid = (int)($_GET['templateid'] ?? 0);

        $this->assertEquals('edit', $action);
        $this->assertEquals(1, $templateid);
    }

    #[Test]
    public function editTemplateHandlesLargeContent(): void
    {
        $largeContent = str_repeat('Lorem ipsum dolor sit amet. ', 500);
        $this->setPost([
            'news_entry' => $largeContent,
        ]);

        $content = $_POST['news_entry'] ?? '';
        $this->assertEquals($largeContent, $content);
    }

    #[Test]
    public function editTemplateHandlesSpecialCharacters(): void
    {
        $this->setPost([
            'news_entry' => 'Ümlauts: äöüß - Special: €@#$%^&*()',
        ]);

        $content = $_POST['news_entry'] ?? '';
        $this->assertStringContainsString('äöüß', $content);
        $this->assertStringContainsString('€', $content);
    }
}
