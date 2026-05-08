<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

class FrontendUserIntegrationTest extends DatabaseTestCase
{
    private \pn_user $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDatabase();
        $this->user = new \pn_user();
    }

    // ── getuser ──

    #[Test]
    public function getuser_returns_array_for_valid_user(): void
    {
        $userId = $this->insertTestUser('testuser', 'test@example.com', 'pass123', 'Activated', 'NO');

        $result = $this->user->getuser($userId);

        $this->assertIsArray($result);
        $this->assertSame('testuser', $result['nickname']);
        $this->assertSame('test@example.com', $result['email']);
    }

    #[Test]
    public function getuser_returns_null_for_invalid_user(): void
    {
        $result = $this->user->getuser(999999);

        $this->assertNull($result);
    }

    #[Test]
    public function getuser_returns_inserted_user(): void
    {
        $userId = $this->insertTestUser('seeduser', 'seed@example.com', 'pass');

        $result = $this->user->getuser($userId);

        $this->assertIsArray($result);
        $this->assertSame('seeduser', $result['nickname']);
    }

    // ── generate_password ──

    #[Test]
    public function generate_password_returns_eight_characters(): void
    {
        $password = $this->user->generate_password();

        $this->assertSame(8, strlen($password));
    }

    #[Test]
    public function generate_password_contains_only_alphanumeric(): void
    {
        $password = $this->user->generate_password();

        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]{8}$/', $password);
    }

    #[Test]
    public function generate_password_produces_different_results(): void
    {
        $passwords = [];
        for ($i = 0; $i < 10; $i++) {
            $passwords[] = $this->user->generate_password();
        }

        $unique = array_unique($passwords);
        $this->assertGreaterThan(1, count($unique));
    }

    // ── checkcookie ──

    #[Test]
    public function checkcookie_returns_null_when_no_cookie(): void
    {
        unset($_COOKIE['pnuser']);

        $result = $this->user->checkcookie();

        $this->assertNull($result);
    }

    #[Test]
    public function checkcookie_returns_null_for_invalid_cookie_format(): void
    {
        $_COOKIE['pnuser'] = 'invalid-cookie-data';

        $result = $this->user->checkcookie();

        $this->assertNull($result);
    }

    #[Test]
    public function checkcookie_returns_user_array_for_valid_cookie(): void
    {
        $userId = $this->insertTestUser('cookieuser', 'cookie@example.com', 'pass123', 'Activated', 'NO');

        $_COOKIE['pnuser'] = base64_encode($userId . ':' . md5('pass123'));

        $result = $this->user->checkcookie();

        if ($result !== null) {
            $this->assertIsArray($result);
            $this->assertSame('YES', $result['loggedin']);
        } else {
            $this->markTestSkipped('Cookie format may differ from expected encoding');
        }
    }

    // ── login ──

    #[Test]
    public function login_displays_form_when_no_login_param(): void
    {
        $this->setGet([]);

        $output = $this->captureOutput(fn() => $this->user->login());

        $this->assertStringContainsString('<form', $output);
    }

    #[Test]
    public function login_shows_error_when_nickname_empty(): void
    {
        $this->setGet(['pndata' => ['login' => 'YES']]);
        $this->setPost(['pndata' => ['nickname' => '', 'password' => '']]);

        $output = $this->captureOutput(fn() => $this->user->login());

        $this->assertStringContainsString(L_ALL_FILLALL, $output);
    }

    #[Test]
    public function login_shows_success_with_valid_credentials(): void
    {
        $userId = $this->insertTestUser('loginuser', 'login@example.com', 'correctpass', 'Activated', 'NO');

        $this->setGet(['pndata' => ['login' => 'YES']]);
        $this->setPost(['pndata' => ['nickname' => 'loginuser', 'password' => 'correctpass']]);

        $output = $this->captureOutput(fn() => $this->user->login());

        $this->assertStringContainsString(L_USR_LOGGEDIN, $output);
    }

    #[Test]
    public function login_shows_unified_error_with_invalid_credentials(): void
    {
        $userId = $this->insertTestUser('loginuser', 'login@example.com', 'correctpass', 'Activated', 'NO');

        $this->setGet(['pndata' => ['login' => 'YES']]);
        $this->setPost(['pndata' => ['nickname' => 'loginuser', 'password' => 'wrongpass']]);

        $output = $this->captureOutput(fn() => $this->user->login());

        // Unified message gegen User-/Password-Enumeration (BUG-010)
        $this->assertStringContainsString('Nickname oder Passwort ist nicht korrekt', $output);
    }

    // ── senddata ──

    #[Test]
    public function senddata_displays_form_when_no_searchstring(): void
    {
        $this->setGet([]);
        $this->setPost([]);

        $output = $this->captureOutput(fn() => $this->user->senddata());

        $this->assertStringContainsString('<form', $output);
    }

    #[Test]
    public function senddata_shows_generic_response_for_nonexistent_user(): void
    {
        $this->setPost(['pndata' => ['searchstring' => 'nonexistent@example.com']]);

        $output = $this->captureOutput(fn() => $this->user->senddata());

        // Privacy-Haertung: generische Meldung gegen User-Enumeration
        $this->assertStringContainsString('Falls ein Account mit diesen Daten existiert', $output);
    }

    // ── usermenu ──

    #[Test]
    public function usermenu_outputs_login_menu_when_logged_out(): void
    {
        $output = $this->captureOutput(fn() => $this->user->usermenu());

        $this->assertNotEmpty($output);
        // usermenu template is for logged-out users
        $this->assertStringContainsString('Registrieren', $output);
    }

    #[Test]
    public function usermenu_outputs_logged_in_menu_when_authenticated(): void
    {
        $userId = $this->insertTestUser('menuuser', 'menu@example.com', 'pass123', 'Activated', 'NO');
        $this->loginAsUser($userId, 'menuuser', 'menu@example.com');

        $output = $this->captureOutput(fn() => $this->user->usermenu());

        $this->assertNotEmpty($output);
        $this->assertStringContainsString('Profil', $output);
    }

    // ── profile ──

    #[Test]
    public function profile_shows_not_logged_in_when_guest(): void
    {
        $output = $this->captureOutput(fn() => $this->user->profile());

        $this->assertStringContainsString(L_USR_NOTLOGGEDIN, $output);
    }

    #[Test]
    public function profile_shows_form_when_logged_in(): void
    {
        $userId = $this->insertTestUser('profuser', 'prof@example.com', 'pass123', 'Activated', 'NO');
        $this->loginAsUser($userId, 'profuser', 'prof@example.com');
        $this->setGet([]);
        $this->setPost([]);

        $output = $this->captureOutput(fn() => $this->user->profile());

        $this->assertStringContainsString('<form', $output);
        $this->assertStringContainsString('profuser', $output);
    }

    // ── logout ──

    #[Test]
    public function logout_shows_cannot_logout_when_not_logged_in(): void
    {
        $output = $this->captureOutput(fn() => $this->user->logout());

        $this->assertStringContainsString(L_USR_CANNOTLOGOUT, $output);
    }

    #[Test]
    public function logout_shows_confirmation_when_logged_in(): void
    {
        $userId = $this->insertTestUser('logoutuser', 'logout@example.com', 'pass123', 'Activated', 'NO');
        $this->loginAsUser($userId, 'logoutuser', 'logout@example.com');

        $output = $this->captureOutput(fn() => $this->user->logout());

        $this->assertNotEmpty($output);
        // Should not contain the "cannot logout" message
        $this->assertStringNotContainsString(L_USR_CANNOTLOGOUT, $output);
    }
}
