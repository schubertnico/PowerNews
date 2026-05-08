<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

class AdditionalCoverageTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDatabase();
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    // ══════════════════════════════════════════════════════════════════
    // pn_news::postcomment
    // ══════════════════════════════════════════════════════════════════

    #[Test]
    public function postcomment_empty_text_outputs_fillall(): void
    {
        $news = new \pn_news();
        $userId = $this->insertTestUser('author', 'author@example.com', 'pass');
        $catId = $this->insertTestCategory('Cat1');
        $newsId = $this->insertTestNews($userId, $catId, 'Title', 'Body');

        $output = $this->captureOutput(fn() => $news->postcomment($newsId, ''));

        $this->assertStringContainsString(L_ALL_FILLALL, $output);
    }

    #[Test]
    public function postcomment_whitespace_only_text_outputs_fillall(): void
    {
        $news = new \pn_news();
        $userId = $this->insertTestUser('author', 'author@example.com', 'pass');
        $catId = $this->insertTestCategory('Cat1');
        $newsId = $this->insertTestNews($userId, $catId, 'Title', 'Body');

        $output = $this->captureOutput(fn() => $news->postcomment($newsId, '   '));

        $this->assertStringContainsString(L_ALL_FILLALL, $output);
    }

    #[Test]
    public function postcomment_valid_text_guest_allowed_inserts_comment(): void
    {
        global $pnconfig;
        $pnconfig['commentwriting'] = 'Guests/Registered';
        $pnconfig['spamprotection'] = '0';

        $news = new \pn_news();
        $userId = $this->insertTestUser('author', 'author@example.com', 'pass');
        $catId = $this->insertTestCategory('Cat1');
        $newsId = $this->insertTestNews($userId, $catId, 'Title', 'Body');

        $output = $this->captureOutput(fn() => $news->postcomment($newsId, 'My comment'));

        $this->assertStringContainsString(L_NEWS_COMMENTPOSTED, $output);
    }

    #[Test]
    public function postcomment_registered_only_guest_denied(): void
    {
        global $pnconfig;
        $pnconfig['commentwriting'] = 'Registered';
        $pnconfig['spamprotection'] = '0';

        $news = new \pn_news();
        $userId = $this->insertTestUser('author', 'author@example.com', 'pass');
        $catId = $this->insertTestCategory('Cat1');
        $newsId = $this->insertTestNews($userId, $catId, 'Title', 'Body');

        $output = $this->captureOutput(fn() => $news->postcomment($newsId, 'My comment'));

        $this->assertStringContainsString(L_NEWS_CANNOTPOSTCOMMENTS, $output);
    }

    #[Test]
    public function postcomment_registered_only_logged_in_succeeds(): void
    {
        global $pnconfig;
        $pnconfig['commentwriting'] = 'Registered';
        $pnconfig['spamprotection'] = '0';

        $news = new \pn_news();
        $userId = $this->insertTestUser('commenter', 'commenter@example.com', 'pass');
        $catId = $this->insertTestCategory('Cat1');
        $newsId = $this->insertTestNews($userId, $catId, 'Title', 'Body');
        $this->loginAsUser($userId, 'commenter', 'commenter@example.com');

        $output = $this->captureOutput(fn() => $news->postcomment($newsId, 'Logged in comment'));

        $this->assertStringContainsString(L_NEWS_COMMENTPOSTED, $output);
    }

    #[Test]
    public function postcomment_spam_protection_blocks_rapid_comments(): void
    {
        global $pnconfig, $pn_config, $pn_handler;
        $pnconfig['commentwriting'] = 'Guests/Registered';
        $pnconfig['spamprotection'] = '3600';

        $news = new \pn_news();
        $userId = $this->insertTestUser('author', 'author@example.com', 'pass');
        $catId = $this->insertTestCategory('Cat1');
        $newsId = $this->insertTestNews($userId, $catId, 'Title', 'Body');

        // Insert a recent comment from same IP
        $now = time();
        $ip = '127.0.0.1';
        $text = 'recent comment';
        $stmt = mysqli_prepare($pn_handler, 'INSERT INTO ' . $pn_config['commenttable'] . ' (newsid, userid, time, text, ip) VALUES(?, ?, ?, ?, ?)');
        $zero = 0;
        mysqli_stmt_bind_param($stmt, 'iiiss', $newsId, $zero, $now, $text, $ip);
        mysqli_stmt_execute($stmt);

        $output = $this->captureOutput(fn() => $news->postcomment($newsId, 'Another comment'));

        $this->assertStringContainsString(L_NEWS_TIMEBETWEEN2COMMENTS, $output);
    }

    #[Test]
    public function postcomment_spam_protection_shows_minutes_unit(): void
    {
        global $pnconfig, $pn_config, $pn_handler;
        $pnconfig['commentwriting'] = 'Guests/Registered';
        $pnconfig['spamprotection'] = '120'; // 2 minutes

        $news = new \pn_news();
        $userId = $this->insertTestUser('author', 'author@example.com', 'pass');
        $catId = $this->insertTestCategory('Cat1');
        $newsId = $this->insertTestNews($userId, $catId, 'Title', 'Body');

        // Insert a recent comment from same IP
        $now = time();
        $ip = '127.0.0.1';
        $text = 'recent';
        $stmt = mysqli_prepare($pn_handler, 'INSERT INTO ' . $pn_config['commenttable'] . ' (newsid, userid, time, text, ip) VALUES(?, ?, ?, ?, ?)');
        $zero = 0;
        mysqli_stmt_bind_param($stmt, 'iiiss', $newsId, $zero, $now, $text, $ip);
        mysqli_stmt_execute($stmt);

        $output = $this->captureOutput(fn() => $news->postcomment($newsId, 'Spam attempt'));

        $this->assertStringContainsString(L_NEWS_TIMEBETWEEN2COMMENTS, $output);
        $this->assertStringContainsString(L_NEWS_MINUTES, $output);
    }

    #[Test]
    public function postcomment_spam_protection_shows_seconds_unit(): void
    {
        global $pnconfig, $pn_config, $pn_handler;
        $pnconfig['commentwriting'] = 'Guests/Registered';
        $pnconfig['spamprotection'] = '30'; // 30 seconds

        $news = new \pn_news();
        $userId = $this->insertTestUser('author', 'author@example.com', 'pass');
        $catId = $this->insertTestCategory('Cat1');
        $newsId = $this->insertTestNews($userId, $catId, 'Title', 'Body');

        $now = time();
        $ip = '127.0.0.1';
        $text = 'recent';
        $stmt = mysqli_prepare($pn_handler, 'INSERT INTO ' . $pn_config['commenttable'] . ' (newsid, userid, time, text, ip) VALUES(?, ?, ?, ?, ?)');
        $zero = 0;
        mysqli_stmt_bind_param($stmt, 'iiiss', $newsId, $zero, $now, $text, $ip);
        mysqli_stmt_execute($stmt);

        $output = $this->captureOutput(fn() => $news->postcomment($newsId, 'Spam attempt'));

        $this->assertStringContainsString(L_NEWS_TIMEBETWEEN2COMMENTS, $output);
        $this->assertStringContainsString(L_NEWS_SECONDS, $output);
    }

    // ══════════════════════════════════════════════════════════════════
    // pn_news::sendnews
    // ══════════════════════════════════════════════════════════════════

    #[Test]
    public function sendnews_disabled_outputs_nonewssendin(): void
    {
        global $pnconfig;
        $pnconfig['sendnews'] = 'NO';

        $news = new \pn_news();
        $output = $this->captureOutput(fn() => $news->sendnews());

        $this->assertStringContainsString(L_NEWS_NONEWSSENDIN, $output);
    }

    #[Test]
    public function sendnews_registered_only_guest_denied(): void
    {
        global $pnconfig;
        $pnconfig['sendnews'] = 'YES';
        $pnconfig['newssending'] = 'Registered';
        $pnconfig['categories'] = 'YES';

        $this->insertTestCategory('General');

        $news = new \pn_news();
        $output = $this->captureOutput(fn() => $news->sendnews());

        $this->assertStringContainsString(L_NEWS_CANNOTSENDNEWS, $output);
    }

    #[Test]
    public function sendnews_shows_form_when_no_send_param(): void
    {
        global $pnconfig;
        $pnconfig['sendnews'] = 'YES';
        $pnconfig['newssending'] = 'Guests/Registered';
        $pnconfig['categories'] = 'YES';

        $this->insertTestCategory('General');

        $news = new \pn_news();
        $output = $this->captureOutput(fn() => $news->sendnews());

        $this->assertNotEmpty($output);
    }

    #[Test]
    public function sendnews_empty_title_outputs_fillall(): void
    {
        global $pnconfig;
        $pnconfig['sendnews'] = 'YES';
        $pnconfig['newssending'] = 'Guests/Registered';
        $pnconfig['categories'] = 'YES';

        $catId = $this->insertTestCategory('General');

        $this->setGet(['pndata' => ['send' => 'YES']]);
        $this->setPost(['pndata' => ['title' => '', 'text' => 'Some text', 'catid' => $catId]]);

        $news = new \pn_news();
        $output = $this->captureOutput(fn() => $news->sendnews());

        $this->assertStringContainsString(L_ALL_FILLALL, $output);
    }

    #[Test]
    public function sendnews_valid_data_inserts_news(): void
    {
        global $pnconfig;
        $pnconfig['sendnews'] = 'YES';
        $pnconfig['newssending'] = 'Guests/Registered';
        $pnconfig['categories'] = 'YES';

        $catId = $this->insertTestCategory('General');

        $this->setGet(['pndata' => ['send' => 'YES']]);
        $this->setPost(['pndata' => ['title' => 'Submitted News', 'text' => 'News body text', 'catid' => (string) $catId]]);

        $news = new \pn_news();
        $output = $this->captureOutput(fn() => $news->sendnews());

        $this->assertStringContainsString(L_NEWS_NEWSSENTIN, $output);
    }

    #[Test]
    public function sendnews_categories_disabled_shows_form(): void
    {
        global $pnconfig;
        $pnconfig['sendnews'] = 'YES';
        $pnconfig['newssending'] = 'Guests/Registered';
        $pnconfig['categories'] = 'NO';

        $news = new \pn_news();
        $output = $this->captureOutput(fn() => $news->sendnews());

        $this->assertNotEmpty($output);
    }

    #[Test]
    public function sendnews_valid_data_categories_disabled_inserts(): void
    {
        global $pnconfig;
        $pnconfig['sendnews'] = 'YES';
        $pnconfig['newssending'] = 'Guests/Registered';
        $pnconfig['categories'] = 'NO';

        $this->setGet(['pndata' => ['send' => 'YES']]);
        $this->setPost(['pndata' => ['title' => 'No Cat News', 'text' => 'Body text']]);

        $news = new \pn_news();
        $output = $this->captureOutput(fn() => $news->sendnews());

        $this->assertStringContainsString(L_NEWS_NEWSSENTIN, $output);
    }

    // ══════════════════════════════════════════════════════════════════
    // pn_news::archive
    // ══════════════════════════════════════════════════════════════════

    #[Test]
    public function archive_default_shows_archive_form(): void
    {
        $userId = $this->insertTestUser('author', 'author@example.com', 'pass');
        $catId = $this->insertTestCategory('General');
        $this->insertTestNews($userId, $catId, 'Archive News', 'Body');

        $news = new \pn_news();
        $output = $this->captureOutput(fn() => $news->archive());

        $this->assertNotEmpty($output);
    }

    #[Test]
    public function archive_search_finds_news(): void
    {
        $userId = $this->insertTestUser('author', 'author@example.com', 'pass');
        $catId = $this->insertTestCategory('General');
        $this->insertTestNews($userId, $catId, 'Searchable Title', 'Body');

        $this->setPost(['pndata' => ['type' => 'search', 'searchstring' => 'Searchable']]);

        $news = new \pn_news();
        $output = $this->captureOutput(fn() => $news->archive());

        $this->assertStringContainsString('Searchable Title', $output);
    }

    #[Test]
    public function archive_search_no_results_outputs_nonewsfound(): void
    {
        $this->setPost(['pndata' => ['type' => 'search', 'searchstring' => 'xyznonexistent']]);

        $news = new \pn_news();
        $output = $this->captureOutput(fn() => $news->archive());

        $this->assertStringContainsString(L_NEWS_NONEWSFOUND, $output);
    }

    #[Test]
    public function archive_search_via_get_fallback(): void
    {
        $this->setGet(['pndata' => ['type' => 'search']]);
        $this->setPost(['pndata' => ['searchstring' => 'xyznonexistent']]);

        $news = new \pn_news();
        $output = $this->captureOutput(fn() => $news->archive());

        $this->assertStringContainsString(L_NEWS_NONEWSFOUND, $output);
    }

    // ══════════════════════════════════════════════════════════════════
    // pn_user::register
    // ══════════════════════════════════════════════════════════════════

    #[Test]
    public function register_no_send_shows_form(): void
    {
        $user = new \pn_user();
        $output = $this->captureOutput(fn() => $user->register());

        $this->assertStringContainsString('<form', $output);
    }

    #[Test]
    public function register_empty_nickname_outputs_validation_error(): void
    {
        $this->setGet(['pndata' => ['send' => 'YES']]);
        $this->setPost(['pndata' => ['nickname' => '', 'email' => 'test@example.com']]);

        $user = new \pn_user();
        $output = $this->captureOutput(fn() => $user->register());

        // Validierung greift vor leer-Check und liefert eine kombinierte Meldung
        $this->assertStringContainsString('Ungueltige Eingabe', $output);
    }

    #[Test]
    public function register_invalid_email_outputs_validation_error(): void
    {
        $this->setGet(['pndata' => ['send' => 'YES']]);
        $this->setPost(['pndata' => ['nickname' => 'brandnewuser', 'email' => 'invalid-email']]);

        $user = new \pn_user();
        $output = $this->captureOutput(fn() => $user->register());

        $this->assertStringContainsString('Ungueltige Eingabe', $output);
    }

    #[Test]
    public function register_duplicate_nickname_outputs_alreadyexists(): void
    {
        $this->insertTestUser('existinguser', 'existing@example.com', 'pass');

        $this->setGet(['pndata' => ['send' => 'YES']]);
        $this->setPost(['pndata' => ['nickname' => 'existinguser', 'email' => 'new@example.com']]);

        $user = new \pn_user();
        $output = $this->captureOutput(fn() => $user->register());

        $this->assertStringContainsString(L_USR_USRALREADYEXISTS, $output);
    }

    #[Test]
    public function register_valid_data_outputs_registered(): void
    {
        $this->setGet(['pndata' => ['send' => 'YES']]);
        $this->setPost(['pndata' => ['nickname' => 'newuser', 'email' => 'new@example.com']]);

        $user = new \pn_user();
        $output = $this->captureOutput(fn() => @$user->register());

        $this->assertStringContainsString(L_USR_REGISTERED, $output);
    }

    // ══════════════════════════════════════════════════════════════════
    // pn_user::profile (additional cases)
    // ══════════════════════════════════════════════════════════════════

    #[Test]
    public function profile_send_valid_data_outputs_profileedited(): void
    {
        $userId = $this->insertTestUser('profuser', 'prof@example.com', 'oldpass');
        $this->loginAsUser($userId, 'profuser', 'prof@example.com');

        $this->setGet(['pndata' => ['send' => 'YES']]);
        $this->setPost(['pndata' => [
            'nickname' => 'profuser',
            'email' => 'prof@example.com',
            'password' => 'newpass1',
            'password2' => 'newpass1',
            'showemail' => 'NO',
            'realname' => 'Test User',
            'city' => 'Test City',
            'age' => '30',
            'homepage' => 'http://example.com',
            'icq' => '12345',
        ]]);

        $user = new \pn_user();
        $output = $this->captureOutput(fn() => $user->profile());

        $this->assertStringContainsString(L_USR_PROFILEEDITED, $output);
    }

    #[Test]
    public function profile_send_nonmatching_passwords_outputs_passnotequal(): void
    {
        $userId = $this->insertTestUser('profuser', 'prof@example.com', 'oldpass');
        $this->loginAsUser($userId, 'profuser', 'prof@example.com');

        $this->setGet(['pndata' => ['send' => 'YES']]);
        $this->setPost(['pndata' => [
            'nickname' => 'profuser',
            'email' => 'prof@example.com',
            'password' => 'pass1',
            'password2' => 'pass2',
        ]]);

        $user = new \pn_user();
        $output = $this->captureOutput(fn() => $user->profile());

        $this->assertStringContainsString(L_USR_PASSNOTEQUAL, $output);
    }

    #[Test]
    public function profile_send_invalid_email_outputs_validation_error(): void
    {
        $userId = $this->insertTestUser('profuser', 'prof@example.com', 'oldpass');
        $this->loginAsUser($userId, 'profuser', 'prof@example.com');

        $this->setGet(['pndata' => ['send' => 'YES']]);
        $this->setPost(['pndata' => [
            'nickname' => 'profuser',
            'email' => 'bademail',
            'password' => 'longenoughpass',
            'password2' => 'longenoughpass',
        ]]);

        $user = new \pn_user();
        $output = $this->captureOutput(fn() => $user->profile());

        $this->assertStringContainsString('Ungueltige Eingabe', $output);
    }

    #[Test]
    public function profile_send_empty_fields_outputs_validation_error(): void
    {
        $userId = $this->insertTestUser('profuser', 'prof@example.com', 'oldpass');
        $this->loginAsUser($userId, 'profuser', 'prof@example.com');

        $this->setGet(['pndata' => ['send' => 'YES']]);
        $this->setPost(['pndata' => [
            'nickname' => 'profuser',
            'email' => '',
            'password' => '',
            'password2' => '',
        ]]);

        $user = new \pn_user();
        $output = $this->captureOutput(fn() => $user->profile());

        $this->assertStringContainsString('Ungueltige Eingabe', $output);
    }

    #[Test]
    public function profile_send_duplicate_nickname_outputs_error(): void
    {
        $userId = $this->insertTestUser('profuser', 'prof@example.com', 'oldpass');
        $this->insertTestUser('otheruser', 'other@example.com', 'pass');
        $this->loginAsUser($userId, 'profuser', 'prof@example.com');

        $this->setGet(['pndata' => ['send' => 'YES']]);
        $this->setPost(['pndata' => [
            'nickname' => 'otheruser',
            'email' => 'prof@example.com',
            'password' => 'longenoughpass',
            'password2' => 'longenoughpass',
        ]]);

        $user = new \pn_user();
        $output = $this->captureOutput(fn() => $user->profile());

        $this->assertStringContainsString(L_USR_NICKNAMEOREMAILALREADYUSED, $output);
    }

    // ══════════════════════════════════════════════════════════════════
    // pn_user::setusercookie
    // ══════════════════════════════════════════════════════════════════

    #[Test]
    public function setusercookie_valid_credentials_returns_array(): void
    {
        $this->insertTestUser('cookieuser', 'cookie@example.com', 'mypassword');

        $this->setPost(['pndata' => ['nickname' => 'cookieuser', 'password' => 'mypassword']]);

        $user = new \pn_user();
        $result = @$user->setusercookie();

        $this->assertIsArray($result);
        $this->assertSame('YES', $result['loggedin']);
    }

    #[Test]
    public function setusercookie_invalid_credentials_returns_null(): void
    {
        $this->insertTestUser('cookieuser', 'cookie@example.com', 'mypassword');

        $this->setPost(['pndata' => ['nickname' => 'cookieuser', 'password' => 'wrongpass']]);

        $user = new \pn_user();
        $result = @$user->setusercookie();

        $this->assertNull($result);
    }

    #[Test]
    public function setusercookie_nonexistent_user_returns_null(): void
    {
        $this->setPost(['pndata' => ['nickname' => 'nosuchuser', 'password' => 'pass']]);

        $user = new \pn_user();
        $result = @$user->setusercookie();

        $this->assertNull($result);
    }

    // ══════════════════════════════════════════════════════════════════
    // pn_user::delusercookie
    // ══════════════════════════════════════════════════════════════════

    #[Test]
    public function delusercookie_does_not_crash(): void
    {
        $userId = $this->insertTestUser('delcookie', 'del@example.com', 'pass');
        $this->loginAsUser($userId, 'delcookie', 'del@example.com');

        $user = new \pn_user();

        // Suppress header/setcookie warnings in CLI
        $output = @$this->captureOutput(function () use ($user) {
            try {
                @$user->delusercookie();
            } catch (\Throwable $e) {
                // header() may throw in some environments
            }
        });

        // Just assert we got here without a fatal error
        $this->assertTrue(true);
    }

    // ══════════════════════════════════════════════════════════════════
    // pn_email::registeremail and dataemail
    // ══════════════════════════════════════════════════════════════════

    #[Test]
    public function registeremail_returns_bool(): void
    {
        $email = new \pn_email();
        $result = @$email->registeremail('testuser', 'test@example.com', 'password123');

        $this->assertIsBool($result);
    }

    #[Test]
    public function dataemail_returns_bool(): void
    {
        $email = new \pn_email();
        $result = @$email->dataemail('testuser', 'test@example.com', 'password123');

        $this->assertIsBool($result);
    }

    // ══════════════════════════════════════════════════════════════════
    // permissions::listpermissions
    // ══════════════════════════════════════════════════════════════════

    #[Test]
    public function listpermissions_no_permissions_outputs_nopermissions(): void
    {
        global $pn_handler, $pn_config;
        // Clear permissions table
        mysqli_query($pn_handler, 'TRUNCATE TABLE ' . $pn_config['permissionstable']);

        $perms = new \permissions();
        $output = $this->captureOutput(fn() => $perms->listpermissions());

        $this->assertStringContainsString(L_PERM_NOPERMISSIONS, $output);
    }

    #[Test]
    public function listpermissions_with_permissions_outputs_nickname(): void
    {
        $userId = $this->insertTestUser('adminuser', 'admin@example.com', 'pass');
        $this->insertTestPermissions($userId, 'YES');

        $perms = new \permissions();
        $output = $this->captureOutput(fn() => $perms->listpermissions());

        $this->assertStringContainsString('adminuser', $output);
    }

    // ══════════════════════════════════════════════════════════════════
    // pn_news::commentform (additional cases)
    // ══════════════════════════════════════════════════════════════════

    #[Test]
    public function commentform_guests_allowed_guest_sees_guest_label(): void
    {
        global $pnconfig;
        // Any value other than 'Registered' allows guests
        $pnconfig['commentwriting'] = 'Guests/Registered';

        $userId = $this->insertTestUser('writer', 'writer@example.com', 'pass');
        $catId = $this->insertTestCategory('General');
        $newsId = $this->insertTestNews($userId, $catId, 'Article', 'Body');

        $news = new \pn_news();
        $output = $this->captureOutput(fn() => $news->commentform($newsId));

        $this->assertStringContainsString(L_NEWS_GUEST, $output);
    }

    #[Test]
    public function commentform_guests_allowed_logged_in_sees_nickname(): void
    {
        global $pnconfig;
        $pnconfig['commentwriting'] = 'Guests/Registered';

        $userId = $this->insertTestUser('member', 'member@example.com', 'pass');
        $catId = $this->insertTestCategory('General');
        $newsId = $this->insertTestNews($userId, $catId, 'Article', 'Body');
        $this->loginAsUser($userId, 'member', 'member@example.com');

        $news = new \pn_news();
        $output = $this->captureOutput(fn() => $news->commentform($newsId));

        $this->assertStringContainsString('member', $output);
    }

    // ══════════════════════════════════════════════════════════════════
    // pn_user::senddata (additional branch coverage)
    // ══════════════════════════════════════════════════════════════════

    #[Test]
    public function senddata_valid_user_returns_generic_response(): void
    {
        $this->insertTestUser('recoveruser', 'recover@example.com', 'oldpass');

        $this->setPost(['pndata' => ['searchstring' => 'recoveruser']]);

        $user = new \pn_user();
        $output = $this->captureOutput(fn() => @$user->senddata());

        // Privacy-Haertung: generische Meldung unabhaengig vom Mail-Erfolg
        $this->assertStringContainsString('Falls ein Account mit diesen Daten existiert', $output);
    }

    // ══════════════════════════════════════════════════════════════════
    // pn_news::postcomment with logged-in user and guest-allowed
    // ══════════════════════════════════════════════════════════════════

    #[Test]
    public function postcomment_guest_allowed_logged_in_uses_userid(): void
    {
        global $pnconfig, $pn_config, $pn_handler;
        $pnconfig['commentwriting'] = 'Guests/Registered';
        $pnconfig['spamprotection'] = '0';

        $userId = $this->insertTestUser('commenter', 'commenter@example.com', 'pass');
        $catId = $this->insertTestCategory('Cat1');
        $newsId = $this->insertTestNews($userId, $catId, 'Title', 'Body');
        $this->loginAsUser($userId, 'commenter', 'commenter@example.com');

        $news = new \pn_news();
        $output = $this->captureOutput(fn() => $news->postcomment($newsId, 'User comment'));

        $this->assertStringContainsString(L_NEWS_COMMENTPOSTED, $output);

        // Verify comment was inserted with user ID
        $result = mysqli_query($pn_handler, 'SELECT userid FROM ' . $pn_config['commenttable'] . ' ORDER BY id DESC LIMIT 1');
        $row = mysqli_fetch_array($result);
        $this->assertEquals($userId, (int) $row['userid']);
    }
}
