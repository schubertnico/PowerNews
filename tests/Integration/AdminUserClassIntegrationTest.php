<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

class AdminUserClassIntegrationTest extends DatabaseTestCase
{
    private \user $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDatabase();
        $this->user = new \user();
    }

    // ── generate_password ──

    #[Test]
    public function generate_password_returns_eight_character_string(): void
    {
        $password = $this->user->generate_password();

        $this->assertSame(8, strlen($password));
    }

    #[Test]
    public function generate_password_returns_alphanumeric_characters_only(): void
    {
        $password = $this->user->generate_password();

        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]{8}$/', $password);
    }

    #[Test]
    public function generate_password_returns_different_values_on_successive_calls(): void
    {
        $passwords = [];
        for ($i = 0; $i < 10; ++$i) {
            $passwords[] = $this->user->generate_password();
        }

        // At least 2 distinct passwords out of 10 calls
        $this->assertGreaterThan(1, count(array_unique($passwords)));
    }

    // ── adduser ──

    #[Test]
    public function adduser_with_valid_data_returns_empty_string(): void
    {
        $result = $this->user->adduser('newuser', 'newuser@example.com', 'NO', 'NO');

        $this->assertSame('', $result);
    }

    #[Test]
    public function adduser_with_valid_data_creates_user_in_database(): void
    {
        global $pn_handler, $pn_config;

        $this->user->adduser('newuser', 'newuser@example.com', 'YES', 'NO');

        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['usertable'] . ' WHERE nickname = ?');
        $nick = 'newuser';
        mysqli_stmt_bind_param($stmt, 's', $nick);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $this->assertSame(1, mysqli_num_rows($result));
        $row = mysqli_fetch_assoc($result);
        $this->assertSame('newuser@example.com', $row['email']);
        $this->assertSame('YES', $row['showemail']);
        $this->assertSame('Activated', $row['status']);
    }

    #[Test]
    public function adduser_with_duplicate_nickname_returns_error(): void
    {
        $this->insertTestUser('existing', 'existing@example.com');

        $result = $this->user->adduser('existing', 'other@example.com', 'NO', 'NO');

        $this->assertSame(L_USR_USRALREADYEXISTS, $result);
    }

    #[Test]
    public function adduser_with_duplicate_email_returns_error(): void
    {
        $this->insertTestUser('existing', 'existing@example.com');

        $result = $this->user->adduser('differentnick', 'existing@example.com', 'NO', 'NO');

        $this->assertSame(L_USR_USRALREADYEXISTS, $result);
    }

    #[Test]
    public function adduser_with_invalid_email_returns_error(): void
    {
        $result = $this->user->adduser('newuser', 'not-an-email', 'NO', 'NO');

        $this->assertSame(L_USR_WRONGEMAIL, $result);
    }

    #[Test]
    public function adduser_with_empty_showemail_defaults_to_no(): void
    {
        global $pn_handler, $pn_config;

        $this->user->adduser('newuser', 'newuser@example.com', '', 'NO');

        $stmt = mysqli_prepare($pn_handler, 'SELECT showemail FROM ' . $pn_config['usertable'] . ' WHERE nickname = ?');
        $nick = 'newuser';
        mysqli_stmt_bind_param($stmt, 's', $nick);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

        $this->assertSame('NO', $row['showemail']);
    }

    // ── checkadmin ──

    #[Test]
    public function checkadmin_returns_yes_for_user_with_permissions(): void
    {
        $userId = $this->insertTestUser('admin', 'admin@example.com');
        $this->insertTestPermissions($userId, 'YES');

        $result = $this->user->checkadmin($userId);

        $this->assertSame('YES', $result);
    }

    #[Test]
    public function checkadmin_returns_no_for_user_without_permissions(): void
    {
        $userId = $this->insertTestUser('regular', 'regular@example.com');

        $result = $this->user->checkadmin($userId);

        $this->assertSame('NO', $result);
    }

    #[Test]
    public function checkadmin_returns_no_for_nonexistent_user(): void
    {
        $result = $this->user->checkadmin(999999);

        $this->assertSame('NO', $result);
    }

    // ── listusers ──

    #[Test]
    public function listusers_outputs_no_users_message_when_empty(): void
    {
        global $pn_handler, $pn_config;
        mysqli_query($pn_handler, 'TRUNCATE TABLE ' . $pn_config['usertable']);

        $output = $this->captureOutput(fn () => $this->user->listusers(0));

        $this->assertStringContainsString(L_USR_NOUSRINDB, $output);
    }

    #[Test]
    public function listusers_outputs_html_table_rows_with_users(): void
    {
        $this->insertTestUser('alice', 'alice@example.com', 'pass', 'Activated', 'YES');
        $this->insertTestUser('bob', 'bob@example.com', 'pass', 'Activated', 'NO');

        $output = $this->captureOutput(fn () => $this->user->listusers(0));

        $this->assertStringContainsString('alice', $output);
        $this->assertStringContainsString('bob', $output);
        $this->assertStringContainsString('<tr>', $output);
        $this->assertStringContainsString('alice@example.com', $output);
    }

    #[Test]
    public function listusers_respects_pagination_offset(): void
    {
        // Insert 30 users so pagination kicks in (25 per page)
        for ($i = 1; $i <= 30; ++$i) {
            $this->insertTestUser("user{$i}", "user{$i}@example.com");
        }

        $outputPage1 = $this->captureOutput(fn () => $this->user->listusers(0));
        $outputPage2 = $this->captureOutput(fn () => $this->user->listusers(25));

        // Page 1 should have 25 users, page 2 should have 5
        $this->assertNotSame($outputPage1, $outputPage2);
        $this->assertStringContainsString('<tr>', $outputPage2);
    }

    // ── checkuser ──

    #[Test]
    public function checkuser_returns_empty_string_for_existing_user(): void
    {
        $userId = $this->insertTestUser('existing', 'existing@example.com');

        $result = $this->user->checkuser($userId);

        $this->assertSame('', $result);
    }

    #[Test]
    public function checkuser_returns_error_for_nonexistent_user(): void
    {
        $result = $this->user->checkuser(999999);

        $this->assertSame(L_USR_NOUSR, $result);
    }

    // ── getuserdata ──

    #[Test]
    public function getuserdata_returns_array_for_existing_user(): void
    {
        $userId = $this->insertTestUser('datauser', 'datauser@example.com', 'pass', 'Activated', 'YES');

        $data = $this->user->getuserdata($userId);

        $this->assertIsArray($data);
        $this->assertSame('datauser', $data['nickname']);
        $this->assertSame('datauser@example.com', $data['email']);
        $this->assertSame('YES', $data['showemail']);
    }

    #[Test]
    public function getuserdata_returns_null_for_nonexistent_user(): void
    {
        $data = $this->user->getuserdata(999999);

        $this->assertNull($data);
    }

    // ── edituser ──

    #[Test]
    public function edituser_with_empty_nickname_returns_error(): void
    {
        $userId = $this->insertTestUser('editme', 'editme@example.com');

        $result = $this->user->edituser('', 'editme@example.com', 'NO', 'NO', 'Activated', 'NO', $userId, '');

        $this->assertSame(L_USR_INSERTNICKNAMEANDEMAIL, $result);
    }

    #[Test]
    public function edituser_with_empty_email_returns_error(): void
    {
        $userId = $this->insertTestUser('editme', 'editme@example.com');

        $result = $this->user->edituser('editme', '', 'NO', 'NO', 'Activated', 'NO', $userId, '');

        $this->assertSame(L_USR_INSERTNICKNAMEANDEMAIL, $result);
    }

    #[Test]
    public function edituser_with_valid_data_returns_empty_string(): void
    {
        $userId = $this->insertTestUser('editme', 'editme@example.com');

        $result = $this->user->edituser('editme2', 'editme2@example.com', 'YES', 'NO', 'Activated', 'NO', $userId, '');

        $this->assertSame('', $result);
    }

    #[Test]
    public function edituser_with_valid_data_updates_database(): void
    {
        $userId = $this->insertTestUser('editme', 'editme@example.com', 'pass', 'Activated', 'NO');

        $this->user->edituser('renamed', 'renamed@example.com', 'YES', 'NO', 'Deactivated', 'NO', $userId, '');

        $data = $this->user->getuserdata($userId);
        $this->assertSame('renamed', $data['nickname']);
        $this->assertSame('renamed@example.com', $data['email']);
        $this->assertSame('YES', $data['showemail']);
        $this->assertSame('Deactivated', $data['status']);
    }

    #[Test]
    public function edituser_with_duplicate_nickname_returns_error(): void
    {
        $this->insertTestUser('taken', 'taken@example.com');
        $userId = $this->insertTestUser('editme', 'editme@example.com');

        $result = $this->user->edituser('taken', 'editme@example.com', 'NO', 'NO', 'Activated', 'NO', $userId, '');

        $this->assertSame(L_USR_USRALREADYEXISTS, $result);
    }

    #[Test]
    public function edituser_with_invalid_email_returns_error(): void
    {
        $userId = $this->insertTestUser('editme', 'editme@example.com');

        $result = $this->user->edituser('editme', 'invalid-email', 'NO', 'NO', 'Activated', 'NO', $userId, '');

        $this->assertSame(L_USR_WRONGEMAIL, $result);
    }

    #[Test]
    public function edituser_with_new_password_generates_new_password(): void
    {
        $userId = $this->insertTestUser('editme', 'editme@example.com', 'oldpass');

        $dataBefore = $this->user->getuserdata($userId);
        $oldHash = $dataBefore['password'];

        $this->user->edituser('editme', 'editme@example.com', 'NO', 'YES', 'Activated', 'NO', $userId, '');

        $dataAfter = $this->user->getuserdata($userId);
        $this->assertNotSame($oldHash, $dataAfter['password']);
    }

    #[Test]
    public function edituser_allows_same_nickname_for_same_user(): void
    {
        $userId = $this->insertTestUser('keepname', 'keepname@example.com');

        $result = $this->user->edituser('keepname', 'keepname@example.com', 'NO', 'NO', 'Activated', 'NO', $userId, '');

        $this->assertSame('', $result);
    }

    // ── listpages ──

    #[Test]
    public function listpages_outputs_pagination_links_for_multiple_users(): void
    {
        for ($i = 1; $i <= 30; ++$i) {
            $this->insertTestUser("user{$i}", "user{$i}@example.com");
        }

        $output = $this->captureOutput(fn () => $this->user->listpages());

        $this->assertStringContainsString('current=0', $output);
        $this->assertStringContainsString('current=25', $output);
    }

    #[Test]
    public function listpages_outputs_no_pages_when_no_users(): void
    {
        global $pn_handler, $pn_config;
        mysqli_query($pn_handler, 'TRUNCATE TABLE ' . $pn_config['usertable']);

        $output = $this->captureOutput(fn () => $this->user->listpages());

        $this->assertStringContainsString('Keine Seiten', $output);
    }

    // ── searchuser ──

    #[Test]
    public function searchuser_outputs_matching_users(): void
    {
        $this->insertTestUser('searchable', 'searchable@example.com');
        $this->insertTestUser('another', 'another@example.com');

        $output = $this->captureOutput(fn () => $this->user->searchuser('nickname', 'searchable', 0));

        $this->assertStringContainsString('searchable', $output);
        $this->assertStringNotContainsString('another', $output);
    }

    #[Test]
    public function searchuser_outputs_not_found_message_when_no_match(): void
    {
        $this->insertTestUser('alice', 'alice@example.com');

        $output = $this->captureOutput(fn () => $this->user->searchuser('nickname', 'zzzznonexistent', 0));

        $this->assertStringContainsString(L_USR_NOUSRFOUND, $output);
    }

    #[Test]
    public function searchuser_searches_by_email_field(): void
    {
        $this->insertTestUser('byemail', 'special-address@example.com');

        $output = $this->captureOutput(fn () => $this->user->searchuser('email', 'special-address', 0));

        $this->assertStringContainsString('byemail', $output);
        $this->assertStringContainsString('special-address@example.com', $output);
    }
}
