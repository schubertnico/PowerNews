<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

class AdminProfileClassIntegrationTest extends DatabaseTestCase
{
    private \profile $profile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDatabase();
        $this->profile = new \profile();
    }

    // ── getdata ──

    #[Test]
    public function getdata_returns_array_for_existing_user(): void
    {
        $userId = $this->insertTestUser('profileuser', 'profile@example.com', 'pass', 'Activated', 'YES');

        $data = $this->profile->getdata($userId);

        $this->assertIsArray($data);
        $this->assertSame('profileuser', $data['nickname']);
        $this->assertSame('profile@example.com', $data['email']);
        $this->assertSame('YES', $data['showemail']);
    }

    #[Test]
    public function getdata_returns_null_for_nonexistent_user(): void
    {
        $data = $this->profile->getdata(999999);

        $this->assertNull($data);
    }

    #[Test]
    public function getdata_returns_complete_user_record(): void
    {
        $userId = $this->insertTestUser('fulldata', 'fulldata@example.com', 'pass', 'Activated', 'NO');

        $data = $this->profile->getdata($userId);

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('nickname', $data);
        $this->assertArrayHasKey('email', $data);
        $this->assertArrayHasKey('password', $data);
        $this->assertArrayHasKey('showemail', $data);
        $this->assertArrayHasKey('status', $data);
    }

    // ── edit ──

    #[Test]
    public function edit_returns_error_for_nonexistent_user(): void
    {
        $result = $this->profile->edit('nick', 'nick@example.com', 'NO', 'pass', 'pass', 999999);

        $this->assertSame(L_USR_NOUSR, $result);
    }

    #[Test]
    public function edit_returns_error_when_passwords_do_not_match(): void
    {
        $userId = $this->insertTestUser('mismatch', 'mismatch@example.com');

        $result = $this->profile->edit('mismatch', 'mismatch@example.com', 'NO', 'password1', 'password2', $userId);

        $this->assertSame(L_USR_PWNOTCONFIRMED, $result);
    }

    #[Test]
    public function edit_returns_error_for_invalid_email(): void
    {
        $userId = $this->insertTestUser('bademail', 'bademail@example.com');

        $result = $this->profile->edit('bademail', 'not-valid-email', 'NO', '', '', $userId);

        $this->assertSame(L_USR_WRONGEMAIL, $result);
    }

    #[Test]
    public function edit_returns_error_for_duplicate_nickname(): void
    {
        $this->insertTestUser('taken', 'taken@example.com');
        $userId = $this->insertTestUser('original', 'original@example.com');

        $result = $this->profile->edit('taken', 'original@example.com', 'NO', '', '', $userId);

        $this->assertSame(L_USR_USRALREADYEXISTS, $result);
    }

    #[Test]
    public function edit_returns_error_for_duplicate_email(): void
    {
        $this->insertTestUser('other', 'other@example.com');
        $userId = $this->insertTestUser('myself', 'myself@example.com');

        $result = $this->profile->edit('myself', 'other@example.com', 'NO', '', '', $userId);

        $this->assertSame(L_USR_USRALREADYEXISTS, $result);
    }

    #[Test]
    public function edit_with_valid_data_and_password_returns_empty_string(): void
    {
        $userId = $this->insertTestUser('editprofile', 'editprofile@example.com');

        $result = $this->profile->edit('editprofile', 'editprofile@example.com', 'YES', 'newpass', 'newpass', $userId);

        $this->assertSame('', $result);
    }

    #[Test]
    public function edit_with_password_updates_password_in_database(): void
    {
        $userId = $this->insertTestUser('pwchange', 'pwchange@example.com', 'oldpass');

        $dataBefore = $this->profile->getdata($userId);
        $oldHash = $dataBefore['password'];

        $this->profile->edit('pwchange', 'pwchange@example.com', 'NO', 'newpassword', 'newpassword', $userId);

        $dataAfter = $this->profile->getdata($userId);
        $this->assertNotSame($oldHash, $dataAfter['password']);
        $this->assertTrue(password_verify('newpassword', $dataAfter['password']));
    }

    #[Test]
    public function edit_without_password_does_not_change_password(): void
    {
        $userId = $this->insertTestUser('keeppass', 'keeppass@example.com', 'originalpass');

        $dataBefore = $this->profile->getdata($userId);
        $oldHash = $dataBefore['password'];

        $this->profile->edit('keeppass', 'keeppass@example.com', 'NO', '', '', $userId);

        $dataAfter = $this->profile->getdata($userId);
        $this->assertSame($oldHash, $dataAfter['password']);
    }

    #[Test]
    public function edit_updates_nickname_and_email_in_database(): void
    {
        $userId = $this->insertTestUser('oldnick', 'oldemail@example.com');

        $this->profile->edit('newnick', 'newemail@example.com', 'YES', '', '', $userId);

        $data = $this->profile->getdata($userId);
        $this->assertSame('newnick', $data['nickname']);
        $this->assertSame('newemail@example.com', $data['email']);
        $this->assertSame('YES', $data['showemail']);
    }

    #[Test]
    public function edit_allows_keeping_same_nickname_and_email(): void
    {
        $userId = $this->insertTestUser('samename', 'samename@example.com');

        $result = $this->profile->edit('samename', 'samename@example.com', 'NO', '', '', $userId);

        $this->assertSame('', $result);
    }

    #[Test]
    public function edit_with_one_empty_password_field_triggers_mismatch(): void
    {
        $userId = $this->insertTestUser('halfpw', 'halfpw@example.com');

        $result = $this->profile->edit('halfpw', 'halfpw@example.com', 'NO', 'somepass', '', $userId);

        $this->assertSame(L_USR_PWNOTCONFIRMED, $result);
    }
}
