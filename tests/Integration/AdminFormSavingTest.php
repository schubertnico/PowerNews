<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

/**
 * Regressionstests für Admin-Formulare, die bis 3.11 mit HTTP 500 abbrachen oder
 * Einstellungen verloren: B05 (Konfiguration), B06 (Profil), B11 (BB-Code/Smilies),
 * B20 (Kategoriebeschreibung) und B45 (Nickname-Regeln).
 */
class AdminFormSavingTest extends DatabaseTestCase
{
    /** @var array<string, mixed> */
    private array $configBackup = [];

    protected function setUp(): void
    {
        parent::setUp();

        global $pn_handler;
        $result = mysqli_query($pn_handler, 'SELECT * FROM pn_config');
        $this->configBackup = (array) mysqli_fetch_assoc($result);
    }

    protected function tearDown(): void
    {
        global $pn_handler;

        $set = [];
        foreach ($this->configBackup as $column => $value) {
            $set[] = '`' . $column . "` = '" . mysqli_real_escape_string($pn_handler, (string) $value) . "'";
        }
        mysqli_query($pn_handler, 'UPDATE pn_config SET ' . implode(', ', $set));

        parent::tearDown();
    }

    /**
     * @param array<string, string> $overrides
     *
     * @return array<string, string>
     */
    private function configPost(array $overrides = []): array
    {
        return array_merge([
            'categories' => 'YES',
            'comments' => 'YES',
            'commentwriting' => 'Registered',
            'moretext' => 'NO',
            'sendnews' => 'YES',
            'newssending' => 'Registered',
            'smilies' => 'Comments',
            'bbcode' => 'Comments/News',
            'dateformat' => 'd.m.Y',
            'timeformat' => 'H:i',
            'template' => '1',
            'url' => 'https://news.example.org',
            'email' => 'redaktion@example.org',
            'headlines' => '10',
            'news' => '10',
            'spamprotection' => '30',
            'relatedlinks' => 'NO',
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function currentConfig(): array
    {
        global $pn_handler;

        return (array) mysqli_fetch_assoc(mysqli_query($pn_handler, 'SELECT * FROM pn_config'));
    }

    #[Test]
    public function configuration_accepts_guests_and_registered(): void
    {
        $output = $this->renderAdminPage('configuration.inc.php', ['page' => 'configuration', 'edit' => 'YES'], $this->configPost(['commentwriting' => 'Guests/Registered']));

        $this->assertStringContainsString(\L_CONF_EDITED, $output);
        $this->assertSame('Guests/Registered', $this->currentConfig()['commentwriting']);
    }

    #[Test]
    public function configuration_keeps_hidden_values_when_comments_are_off(): void
    {
        global $pn_handler, $pnconfig;

        mysqli_query($pn_handler, "UPDATE pn_config SET comments = 'NO', commentwriting = 'Guests/Registered', sendnews = 'NO', newssending = 'Registered', html = 'News'");
        $pnconfig = $this->currentConfig();

        // Bei abgeschalteten Kommentaren/Einsendungen fehlen die Auswahlfelder im Formular.
        $post = $this->configPost(['comments' => 'NO', 'sendnews' => 'NO']);
        unset($post['commentwriting'], $post['newssending']);

        $output = $this->renderAdminPage('configuration.inc.php', ['page' => 'configuration', 'edit' => 'YES'], $post);

        $this->assertStringContainsString(\L_CONF_EDITED, $output);
        $config = $this->currentConfig();
        $this->assertSame('Guests/Registered', $config['commentwriting']);
        $this->assertSame('Registered', $config['newssending']);
        $this->assertSame('News', $config['html'], 'Die ausgeblendete HTML-Option behält ihren Wert.');
    }

    #[Test]
    public function saving_configuration_keeps_bbcode_and_smilies(): void
    {
        $this->renderAdminPage('configuration.inc.php', ['page' => 'configuration', 'edit' => 'YES'], $this->configPost(['smilies' => 'Comments/News', 'bbcode' => 'News']));

        $config = $this->currentConfig();
        $this->assertSame('Comments/News', $config['smilies']);
        $this->assertSame('News', $config['bbcode']);
    }

    #[Test]
    public function configuration_form_no_longer_offers_ineffective_html_option(): void
    {
        $output = $this->renderAdminPage('configuration.inc.php', ['page' => 'configuration']);

        $this->assertStringNotContainsString('id="cfg_html"', $output);
        $this->assertStringContainsString('<option value="Guests/Registered"', $output);
    }

    #[Test]
    public function profile_saves_without_showemail_checkbox(): void
    {
        $userId = $this->insertTestUser('profiladmin', 'profiladmin@example.com', 'altes-passwort', 'Activated', 'YES');
        $this->loginAsUser($userId, 'profiladmin', 'profiladmin@example.com');

        $output = $this->renderAdminPage('profile.inc.php', ['page' => 'profile', 'edit' => 'YES'], [
            'nickname' => 'profiladmin',
            'email' => 'profiladmin@example.com',
            'password' => '',
            'password2' => '',
        ]);

        $this->assertStringContainsString(\L_USR_PROFILEEDITED, $output);
        $this->assertSame('NO', (new \profile())->getdata($userId)['showemail']);
    }

    #[Test]
    public function profile_rejects_invalid_address_with_readable_message(): void
    {
        $userId = $this->insertTestUser('localadmin', 'localadmin@example.com');
        $this->loginAsUser($userId, 'localadmin', 'localadmin@example.com');

        $output = $this->renderAdminPage('profile.inc.php', ['page' => 'profile', 'edit' => 'YES'], [
            'nickname' => 'localadmin',
            'email' => 'not-an-address',
        ]);

        $this->assertStringContainsString(pnadmin_escape(\L_USR_WRONGEMAIL), $output);
        $this->assertStringNotContainsString('&amp;uuml;', $output);
    }

    #[Test]
    public function category_description_over_255_bytes_is_rejected_without_error(): void
    {
        global $pn_handler;

        $output = $this->renderAdminPage('categories_add.inc.php', ['page' => 'categories', 'subpage' => 'add', 'add' => 'YES'], [
            'name' => 'Lange Beschreibung',
            'description' => str_repeat('Ä', 200),
        ]);

        $this->assertStringContainsString(pnadmin_escape(\L_CAT_DESCRIPTIONTOOLONG), $output);
        $result = mysqli_query($pn_handler, "SELECT id FROM pn_categories WHERE name = 'Lange Beschreibung'");
        $this->assertSame(0, mysqli_num_rows($result));
    }

    #[Test]
    public function admin_uses_the_frontend_nickname_rules(): void
    {
        $user = new \user();

        $this->assertSame(\L_USR_INVALIDNICKNAME, $user->adduser('Max Müller', 'max.mueller@example.com', 'NO', 'NO'));
        $this->assertSame('', $user->adduser('Max.Müller', 'max.mueller@example.com', 'NO', 'NO'));

        $userId = $this->insertTestUser('nickrules', 'nickrules@example.com');
        $this->assertSame(\L_USR_INVALIDNICKNAME, $user->edituser('nick rules', 'nickrules@example.com', 'NO', 'NO', 'Activated', 'NO', $userId, ''));
        $this->assertSame(\L_USR_INVALIDNICKNAME, (new \profile())->edit('mit leerzeichen', 'nickrules@example.com', 'NO', '', '', $userId));
    }

    #[Test]
    public function database_errors_are_reported_instead_of_fatal(): void
    {
        global $pn_handler;

        $stmt = mysqli_prepare($pn_handler, "UPDATE pn_config SET commentwriting = ?");
        $invalid = 'Guests & Registered';
        mysqli_stmt_bind_param($stmt, 's', $invalid);

        $this->assertFalse(pnadmin_execute($stmt));
    }
}
