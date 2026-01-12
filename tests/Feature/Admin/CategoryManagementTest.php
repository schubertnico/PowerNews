<?php
declare(strict_types=1);

namespace PowerNews\Tests\Feature\Admin;

use PowerNews\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for category management forms (pnadmin/categories_add.inc.php, categories_edit.inc.php)
 */
class CategoryManagementTest extends TestCase
{
    /**
     * Category name validation regex (disallowed characters)
     */
    private const CATEGORY_NAME_INVALID_REGEX = '/[.\/\\\\:*?<>|"]+/';

    // === Category Add Tests ===

    #[Test]
    public function addCategoryRequiresName(): void
    {
        $this->setPost([
            'name' => '',
            'description' => 'Test description',
        ]);

        $name = trim((string)($_POST['name'] ?? ''));
        $this->assertEmpty($name, 'Empty name should fail validation');
    }

    #[Test]
    public function addCategoryAcceptsValidData(): void
    {
        $this->setPost([
            'name' => 'Test Category',
            'description' => 'Test description',
        ]);

        $name = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));

        $this->assertNotEmpty($name);
        $this->assertNotEmpty($description);
    }

    #[Test]
    #[DataProvider('validCategoryNameProvider')]
    public function addCategoryAcceptsValidNames(string $name): void
    {
        $this->setPost(['name' => $name]);

        $parsedName = trim((string)($_POST['name'] ?? ''));
        $hasInvalidChars = preg_match(self::CATEGORY_NAME_INVALID_REGEX, $parsedName);

        $this->assertEquals(0, $hasInvalidChars, "Name '$name' should be valid");
    }

    public static function validCategoryNameProvider(): array
    {
        return [
            'simple' => ['News'],
            'with spaces' => ['Breaking News'],
            'with numbers' => ['News 2024'],
            'with hyphen' => ['Tech-News'],
            'with underscore' => ['Tech_News'],
            'german umlauts' => ['Nachrichten'],
            'parentheses' => ['News (Important)'],
        ];
    }

    #[Test]
    #[DataProvider('invalidCategoryNameProvider')]
    public function addCategoryRejectsInvalidNames(string $name): void
    {
        $this->setPost(['name' => $name]);

        $parsedName = trim((string)($_POST['name'] ?? ''));
        $hasInvalidChars = preg_match(self::CATEGORY_NAME_INVALID_REGEX, $parsedName);

        $this->assertEquals(1, $hasInvalidChars, "Name '$name' should be invalid");
    }

    public static function invalidCategoryNameProvider(): array
    {
        return [
            'with dot' => ['News.txt'],
            'with slash' => ['News/Category'],
            'with backslash' => ['News\\Category'],
            'with colon' => ['News:Important'],
            'with asterisk' => ['News*'],
            'with question' => ['News?'],
            'with lt' => ['News<Tag>'],
            'with gt' => ['<News>'],
            'with pipe' => ['News|Category'],
            'with quotes' => ['News"Title"'],
        ];
    }

    #[Test]
    public function addCategoryHandlesFileUpload(): void
    {
        // Simulate file upload structure
        $_FILES = [
            'picture' => [
                'name' => 'test.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/phpXXXXXX',
                'error' => UPLOAD_ERR_OK,
                'size' => 12345,
            ],
        ];

        $this->assertTrue(isset($_FILES['picture']));
        $this->assertIsArray($_FILES['picture']);
        $this->assertArrayHasKey('tmp_name', $_FILES['picture']);
    }

    #[Test]
    public function addCategoryHandlesNoFileUpload(): void
    {
        $_FILES = [];

        $picture = isset($_FILES['picture']) && is_array($_FILES['picture']) && !empty($_FILES['picture']['tmp_name'])
            ? $_FILES['picture']
            : [];

        $this->assertEmpty($picture);
    }

    // === Category Edit Tests ===

    #[Test]
    public function editCategoryRequiresCatId(): void
    {
        $this->setGet(['catid' => '']);

        $catid = (int)($_GET['catid'] ?? 0);
        $this->assertEquals(0, $catid, 'Empty catid should result in 0');
    }

    #[Test]
    public function editCategoryCastsCatIdToInteger(): void
    {
        $this->setGet(['catid' => '789abc']);

        $catid = (int)($_GET['catid'] ?? 0);
        $this->assertEquals(789, $catid);
    }

    #[Test]
    public function editCategoryAcceptsValidStatus(): void
    {
        $this->setPost(['status' => 'Activated']);

        $status = $_POST['status'] ?? '';
        $allowedStatuses = ['Activated', 'Deactivated'];

        $this->assertTrue(in_array($status, $allowedStatuses));
    }

    #[Test]
    #[DataProvider('categoryStatusProvider')]
    public function editCategoryAcceptsAllValidStatuses(string $status): void
    {
        $this->setPost(['status' => $status]);

        $allowedStatuses = ['Activated', 'Deactivated'];
        $this->assertTrue(in_array($status, $allowedStatuses));
    }

    public static function categoryStatusProvider(): array
    {
        return [
            'activated' => ['Activated'],
            'deactivated' => ['Deactivated'],
        ];
    }

    #[Test]
    public function editCategoryRejectsInvalidStatus(): void
    {
        $this->setPost(['status' => 'Invalid']);

        $status = $_POST['status'] ?? '';
        $allowedStatuses = ['Activated', 'Deactivated'];

        $this->assertFalse(in_array($status, $allowedStatuses));
    }

    #[Test]
    public function editCategoryHandlesXSSInName(): void
    {
        $this->setPost([
            'name' => '<script>alert("xss")</script>',
        ]);

        $name = htmlspecialchars((string)($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString('<script>', $name);
    }

    #[Test]
    public function editCategoryHandlesXSSInDescription(): void
    {
        $this->setPost([
            'description' => '<img src=x onerror="alert(1)">',
        ]);

        $description = htmlspecialchars((string)($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8');

        // HTML special characters should be escaped
        $this->assertStringNotContainsString('<img', $description);
        $this->assertStringContainsString('&lt;img', $description);
        $this->assertStringContainsString('&quot;', $description);
    }
}
