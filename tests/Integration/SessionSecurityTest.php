<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

/**
 * Regressionstests für B04/B33 (gemeinsames Cookie für Frontend und Admin), B25 (Passwort-Hash
 * als Sitzungsnachweis), B26 (Sitzungsdauer, Aufräumen, Passwortwechsel) und B32
 * (eingeloggt „Login“ aufrufen).
 */
class SessionSecurityTest extends DatabaseTestCase
{
    private function sessionCount(int $userId): int
    {
        global $pn_handler;

        $result = pn_query_by_id($pn_handler, 'SELECT COUNT(*) FROM pn_sessions WHERE userid = ?', $userId);

        return (int) mysqli_fetch_row($result)[0];
    }

    #[Test]
    public function frontend_and_admin_use_separate_cookies_and_scopes(): void
    {
        global $pn_handler;

        $this->assertNotSame(PN_COOKIE_FRONTEND, PN_COOKIE_ADMIN);

        $userId = $this->insertTestUser('beides', 'beides@example.com');
        $frontendToken = pn_session_create($pn_handler, $userId, 'frontend');
        $adminToken = pn_session_create($pn_handler, $userId, 'admin');

        // Ein Frontend-Token taugt nicht als Admin-Sitzung und umgekehrt.
        $this->assertFalse(pn_session_validate($pn_handler, $userId, $frontendToken, 'admin'));
        $this->assertFalse(pn_session_validate($pn_handler, $userId, $adminToken, 'frontend'));
        $this->assertTrue(pn_session_validate($pn_handler, $userId, $frontendToken, 'frontend'));
        $this->assertTrue(pn_session_validate($pn_handler, $userId, $adminToken, 'admin'));
    }

    #[Test]
    public function malformed_admin_cookies_are_ignored_without_error(): void
    {
        // B04: Früher warf explode() auf das Ergebnis von base64_decode() einen TypeError.
        foreach (['12:' . str_repeat('a', 64) . 'x', base64_encode('1@@@@@abc'), 'kein-cookie', '0:' . str_repeat('b', 64), ':'] as $value) {
            $_COOKIE[PN_COOKIE_ADMIN] = $value;
            $this->assertNull(pn_session_parse_cookie(PN_COOKIE_ADMIN), $value);
            $this->assertNull(pnadmin_auth_check());
        }

        $_COOKIE[PN_COOKIE_ADMIN] = '7:' . str_repeat('c', 64);
        $this->assertSame([7, str_repeat('c', 64)], pn_session_parse_cookie(PN_COOKIE_ADMIN));
    }

    #[Test]
    public function frontend_cookie_does_not_grant_admin_access(): void
    {
        global $pn_handler;

        $userId = $this->insertTestUser('leser_admin', 'leser_admin@example.com');
        $this->insertTestPermissions($userId);
        $token = pn_session_create($pn_handler, $userId, 'frontend');

        // Frontend-Cookie gesetzt, Admin-Cookie fehlt: keine Admin-Anmeldung.
        $_COOKIE[PN_COOKIE_FRONTEND] = $userId . ':' . $token;
        $this->assertNull(pnadmin_auth_check());

        // Selbst unter dem Admin-Namen gilt das Frontend-Token nicht.
        $_COOKIE[PN_COOKIE_ADMIN] = $userId . ':' . $token;
        $this->assertNull(pnadmin_auth_check());
    }

    #[Test]
    public function admin_login_creates_short_session_with_admin_cookie(): void
    {
        global $pn_handler, $pncookie;

        $userId = $this->insertTestUser('kurz_admin', 'kurz_admin@example.com', 'passwort-123');
        $this->insertTestPermissions($userId);

        $this->assertSame('loggedin', (new \login())->checklogin('kurz_admin', 'passwort-123'));
        [$cookieUser, $token] = explode(':', (string) $pncookie, 2);
        $this->assertSame((string) $userId, $cookieUser);

        $result = pn_query_by_id($pn_handler, 'SELECT created, expires FROM pn_sessions WHERE userid = ?', $userId);
        $row = mysqli_fetch_assoc($result);
        $this->assertLessThanOrEqual(PN_SESSION_ADMIN_IDLE, (int) $row['expires'] - (int) $row['created']);

        $_COOKIE[PN_COOKIE_ADMIN] = $pncookie;
        $this->assertSame($userId, (int) pnadmin_auth_check()['userid']);
        $this->assertTrue(pn_session_validate($pn_handler, $userId, $token, 'admin'));
    }

    #[Test]
    public function admin_session_slides_but_ends_after_maximum_lifetime(): void
    {
        global $pn_handler;

        $userId = $this->insertTestUser('gleit_admin', 'gleit_admin@example.com');
        $token = pn_session_create($pn_handler, $userId, 'admin');
        $hash = pn_session_hash($token, 'admin');

        // Seit 2 Stunden aktiv, läuft in 10 Minuten ab: Aktivität verlängert auf 8 Stunden.
        $now = time();
        $stmt = mysqli_prepare($pn_handler, 'UPDATE pn_sessions SET created = ?, expires = ? WHERE token_hash = ?');
        $created = $now - 7200;
        $expires = $now + 600;
        mysqli_stmt_bind_param($stmt, 'iis', $created, $expires, $hash);
        mysqli_stmt_execute($stmt);

        $this->assertTrue(pn_session_validate($pn_handler, $userId, $token, 'admin'));
        $row = mysqli_fetch_assoc(mysqli_query($pn_handler, "SELECT expires FROM pn_sessions WHERE token_hash = '" . $hash . "'"));
        $this->assertGreaterThan($now + PN_SESSION_ADMIN_IDLE - 60, (int) $row['expires']);

        // Nach 24 Stunden ist Schluss, auch bei Aktivität.
        $created = $now - PN_SESSION_ADMIN_MAX - 10;
        $expires = $now + 600;
        mysqli_stmt_bind_param($stmt, 'iis', $created, $expires, $hash);
        mysqli_stmt_execute($stmt);

        $this->assertFalse(pn_session_validate($pn_handler, $userId, $token, 'admin'));
        $this->assertSame(0, $this->sessionCount($userId));
    }

    #[Test]
    public function expired_sessions_are_purged_on_login(): void
    {
        global $pn_handler;

        $userId = $this->insertTestUser('alt_sitzung', 'alt_sitzung@example.com');
        $stmt = mysqli_prepare($pn_handler, "INSERT INTO pn_sessions (userid, token_hash, created, expires, user_agent, ip) VALUES (?, ?, ?, ?, '', '')");
        $hash = str_repeat('d', 64);
        $created = time() - 100000;
        $expires = time() - 50000;
        mysqli_stmt_bind_param($stmt, 'isii', $userId, $hash, $created, $expires);
        mysqli_stmt_execute($stmt);

        pn_session_create($pn_handler, $userId, 'frontend');

        $result = mysqli_query($pn_handler, "SELECT COUNT(*) FROM pn_sessions WHERE token_hash = '" . $hash . "'");
        $this->assertSame(0, (int) mysqli_fetch_row($result)[0]);
    }

    #[Test]
    public function password_changes_end_all_sessions(): void
    {
        global $pn_handler;

        $userId = $this->insertTestUser('pw_wechsel', 'pw_wechsel@example.com');
        pn_session_create($pn_handler, $userId, 'frontend');
        pn_session_create($pn_handler, $userId, 'admin');
        $this->assertSame(2, $this->sessionCount($userId));

        $this->assertSame('', (new \profile())->edit('pw_wechsel', 'pw_wechsel@example.com', 'NO', 'neues-passwort', 'neues-passwort', $userId));
        $this->assertSame(0, $this->sessionCount($userId));

        pn_session_create($pn_handler, $userId, 'frontend');
        @(new \user())->edituser('pw_wechsel', 'pw_wechsel@example.com', 'NO', 'YES', 'Activated', 'NO', $userId, '');
        $this->assertSame(0, $this->sessionCount($userId), 'Neues Passwort durch einen Admin beendet Sitzungen.');

        pn_session_create($pn_handler, $userId, 'frontend');
        (new \user())->edituser('pw_wechsel', 'pw_wechsel@example.com', 'NO', 'NO', 'Deactivated', 'NO', $userId, '');
        $this->assertSame(0, $this->sessionCount($userId), 'Deaktivieren beendet Sitzungen.');
    }

    #[Test]
    public function frontend_password_change_ends_sessions(): void
    {
        global $pn_handler;

        $userId = $this->insertTestUser('fe_wechsel', 'fe_wechsel@example.com');
        pn_session_create($pn_handler, $userId, 'frontend');
        $this->loginAsUser($userId, 'fe_wechsel', 'fe_wechsel@example.com');
        $this->setGet(['pndata' => ['send' => 'YES']]);
        $this->setPost(['pndata' => [
            'nickname' => 'fe_wechsel',
            'email' => 'fe_wechsel@example.com',
            'password' => 'ganz-neues-pw',
            'password2' => 'ganz-neues-pw',
        ]]);

        $output = $this->captureOutput(fn () => (new \pn_user())->profile());

        $this->assertStringContainsString(\L_USR_PROFILEEDITED, $output);
        $this->assertSame(0, $this->sessionCount($userId));
    }

    #[Test]
    public function legacy_admin_sessions_are_removed_by_migration(): void
    {
        global $pn_handler, $pn_config;

        mysqli_query($pn_handler, "DELETE FROM pn_migrations WHERE name = '3.12-purge-legacy-admin-sessions'");
        $userId = $this->insertTestUser('legacy_sess', 'legacy_sess@example.com');
        $stmt = mysqli_prepare($pn_handler, "INSERT INTO pn_sessions (userid, token_hash, created, expires, user_agent, ip) VALUES (?, ?, ?, ?, '', '')");
        $hash = str_repeat('e', 64);
        $created = time() - 3600;
        $expires = $created + 360 * 86400;
        mysqli_stmt_bind_param($stmt, 'isii', $userId, $hash, $created, $expires);
        mysqli_stmt_execute($stmt);
        pn_session_create($pn_handler, $userId, 'frontend');

        $log = pn_run_migrations($pn_handler, $pn_config);

        $this->assertArrayHasKey('3.12-purge-legacy-admin-sessions', $log);
        $this->assertSame(1, $this->sessionCount($userId), 'Nur die 360-Tage-Sitzung wird entfernt.');
    }

    #[Test]
    public function login_page_for_logged_in_user_shows_link_instead_of_blank_page(): void
    {
        $userId = $this->insertTestUser('schon_da', 'schon_da@example.com');
        $this->loginAsUser($userId, 'schon_da', 'schon_da@example.com');
        $this->setGet(['page' => 'login']);

        $output = $this->captureOutput(fn () => (new \pn_user())->login());

        $this->assertStringContainsString(\L_USR_ALREADYLOGGEDIN, $output);
        $this->assertStringContainsString('user.php?page=profile', $output);
    }

    #[Test]
    public function comment_form_works_for_guests_without_warnings(): void
    {
        global $pnuser, $pnconfig;

        // B33: Mit fremdem Cookie war $pnuser früher null.
        $pnuser = ['loggedin' => 'NO'];
        $pnconfig['commentwriting'] = 'Registered';

        $output = $this->captureOutput(fn () => (new \pn_news())->commentform(1));

        $this->assertStringContainsString(\L_NEWS_CANNOTPOSTCOMMENTS, $output);
    }
}
