<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

class AdminCategoryIntegrationTest extends DatabaseTestCase
{
    private \category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDatabase();
        $this->category = new \category();
    }

    // ── addcat ──

    #[Test]
    public function addcat_returns_empty_string_on_valid_name(): void
    {
        $result = $this->category->addcat('Technology', 'Tech articles');

        $this->assertSame('', $result);
    }

    #[Test]
    public function addcat_inserts_category_into_database(): void
    {
        global $pn_handler, $pn_config;

        $this->category->addcat('Science', 'Science articles');

        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['cattable'] . ' WHERE name = ?');
        $name = addslashes('Science');
        mysqli_stmt_bind_param($stmt, 's', $name);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $this->assertSame(1, mysqli_num_rows($result));
    }

    #[Test]
    public function addcat_returns_error_on_duplicate_name(): void
    {
        $this->insertTestCategory('Existing');

        $result = $this->category->addcat('Existing', 'Another one');

        $this->assertSame(L_CAT_OTHERCATWITHTITLEEXISTS, $result);
    }

    #[Test]
    public function addcat_returns_error_on_dot_in_name(): void
    {
        $result = $this->category->addcat('bad.name', 'Description');

        $this->assertSame(L_CAT_WRONGCATTITLE, $result);
    }

    #[Test]
    public function addcat_returns_error_on_slash_in_name(): void
    {
        $result = $this->category->addcat('bad/name', 'Description');

        $this->assertSame(L_CAT_WRONGCATTITLE, $result);
    }

    #[Test]
    public function addcat_returns_error_on_angle_bracket_in_name(): void
    {
        $result = $this->category->addcat('bad<name', 'Description');

        $this->assertSame(L_CAT_WRONGCATTITLE, $result);
    }

    #[Test]
    public function addcat_without_picture_leaves_picture_empty(): void
    {
        global $pn_handler, $pn_config;

        $this->category->addcat('NoPic', 'No picture');

        $result = mysqli_query($pn_handler, 'SELECT picture FROM ' . $pn_config['cattable'] . " WHERE name = 'NoPic'");
        $row = mysqli_fetch_assoc($result);

        $this->assertSame('', $row['picture']);
    }

    // ── listcats ──

    #[Test]
    public function listcats_outputs_no_cats_message_when_empty(): void
    {
        $this->truncateAll();

        $output = $this->captureOutput(fn() => $this->category->listcats());

        $this->assertStringContainsString(L_CAT_NOCATSAVAILABLE, $output);
    }

    #[Test]
    public function listcats_outputs_category_names_in_table_rows(): void
    {
        $this->insertTestCategory('Sports');
        $this->insertTestCategory('Politics');

        $output = $this->captureOutput(fn() => $this->category->listcats());

        $this->assertStringContainsString('Sports', $output);
        $this->assertStringContainsString('Politics', $output);
        $this->assertStringContainsString('<tr>', $output);
    }

    // ── checkcat ──

    #[Test]
    public function checkcat_returns_empty_string_for_existing_category(): void
    {
        $catId = $this->insertTestCategory('Valid');

        $result = $this->category->checkcat($catId);

        $this->assertSame('', $result);
    }

    #[Test]
    public function checkcat_returns_error_for_nonexistent_category(): void
    {
        $result = $this->category->checkcat(999999);

        $this->assertSame(L_CAT_NONEXISTINGCAT, $result);
    }

    // ── getcatdata ──

    #[Test]
    public function getcatdata_returns_array_for_existing_category(): void
    {
        $catId = $this->insertTestCategory('DataCat', 'Activated', 'Cat description');

        $data = $this->category->getcatdata($catId);

        $this->assertIsArray($data);
        $this->assertSame('DataCat', $data['name']);
        $this->assertSame('Cat description', $data['description']);
    }

    #[Test]
    public function getcatdata_returns_null_for_nonexistent_category(): void
    {
        $data = $this->category->getcatdata(999999);

        $this->assertNull($data);
    }

    // ── editcat ──

    #[Test]
    public function editcat_returns_error_for_nonexistent_catid(): void
    {
        $result = $this->category->editcat('NewName', 'desc', '', [], 'Activated', 999999);

        $this->assertSame(L_CAT_NONEXISTINGCAT, $result);
    }

    #[Test]
    public function editcat_returns_empty_string_on_valid_edit(): void
    {
        $catId = $this->insertTestCategory('Original');

        $result = $this->category->editcat('Updated', 'New desc', '', [], 'Activated', $catId);

        $this->assertSame('', $result);
    }

    #[Test]
    public function editcat_updates_category_in_database(): void
    {
        $catId = $this->insertTestCategory('BeforeEdit');

        $this->category->editcat('AfterEdit', 'Updated desc', '', [], 'Deactivated', $catId);

        $data = $this->category->getcatdata($catId);
        $this->assertSame('AfterEdit', $data['name']);
        $this->assertSame('Updated desc', $data['description']);
        $this->assertSame('Deactivated', $data['status']);
    }

    #[Test]
    public function editcat_returns_error_on_duplicate_name_with_other_cat(): void
    {
        $this->insertTestCategory('TakenName');
        $catId = $this->insertTestCategory('MyCategory');

        $result = $this->category->editcat('TakenName', 'desc', '', [], 'Activated', $catId);

        $this->assertSame(L_CAT_OTHERCATWITHTITLEEXISTS, $result);
    }

    #[Test]
    public function editcat_allows_keeping_same_name(): void
    {
        $catId = $this->insertTestCategory('KeepName');

        $result = $this->category->editcat('KeepName', 'new desc', '', [], 'Activated', $catId);

        $this->assertSame('', $result);
    }

    #[Test]
    public function editcat_returns_error_on_invalid_chars_in_name(): void
    {
        $catId = $this->insertTestCategory('ValidCat');

        $result = $this->category->editcat('invalid.name', 'desc', '', [], 'Activated', $catId);

        $this->assertSame(L_CAT_WRONGCATTITLE, $result);
    }
}
