<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

/**
 * Das Häkchen „showemail“ macht den Autornamen unter News und Kommentaren zum Mail-Link.
 * Ein öffentliches Profil gibt es nicht; die Beschriftung sagt das jetzt ehrlich, und neue
 * Konten verlinken ihre Adresse nicht (Privacy by Default).
 */
class PublicEmailTest extends DatabaseTestCase
{
    private const string LABEL = 'Namen mit E-Mail-Adresse verlinken (öffentlich sichtbar)';

    #[Test]
    public function registration_labels_the_public_mail_link_and_leaves_it_off(): void
    {
        $form = $this->captureOutput(fn () => (new \pn_user())->register());

        $this->assertStringContainsString('<input class="form-check-input" type="checkbox" name="pndata[showemail]" value="YES" id="pn_showemail" aria-describedby="pn_showemail_help"><label class="form-check-label" for="pn_showemail">' . self::LABEL . '</label>', $form);
        $this->assertStringContainsString('id="pn_showemail_help"', $form);
        $this->assertStringNotContainsString('im Profil anzeigen', $form);
    }

    #[Test]
    public function profile_form_uses_the_same_label(): void
    {
        $output = $this->captureOutput(fn () => (new \pn_template())->profileform(['nickname' => 'leser', 'email' => 'leser@example.org', 'showemail' => 'YES']));

        $this->assertStringContainsString('id="pn_showemail" checked aria-describedby="pn_showemail_help"', $output);
        $this->assertStringContainsString(self::LABEL, $output);

        $off = $this->captureOutput(fn () => (new \pn_template())->profileform(['nickname' => 'leser', 'email' => 'leser@example.org', 'showemail' => 'NO']));
        $this->assertStringContainsString('id="pn_showemail"  aria-describedby="pn_showemail_help"', $off);
    }

    #[Test]
    public function admin_forms_and_list_use_the_honest_wording(): void
    {
        $add = $this->renderAdminPage('users_add.inc.php', []);
        $this->assertStringContainsString('<input class="form-check-input" type="checkbox" name="showemail" value="YES" id="pn_showemail" aria-describedby="pn_showemail_help">', $add, 'Voreinstellung nein');
        $this->assertStringContainsString('<label class="form-check-label fw-bold" for="pn_showemail">' . \L_USR_SHOWEMAIL . '</label>', $add);

        $this->insertTestUser('listenleser', 'listenleser@example.org');
        $list = $this->renderAdminPage('users_show.inc.php', []);
        $this->assertStringContainsString('<th class="text-center">' . \L_USR_SHOWEMAIL_COLUMN . '</th>', $list);
    }

    #[Test]
    public function new_accounts_do_not_link_their_address_by_default(): void
    {
        global $pn_handler;

        mysqli_query($pn_handler, "INSERT INTO pn_users (nickname, email, password, registered) VALUES ('ohnewahl', 'ohnewahl@example.org', 'x', 0)");
        $row = mysqli_fetch_assoc(pn_query_by_string($pn_handler, 'SELECT showemail FROM pn_users WHERE nickname = ?', 'ohnewahl'));

        $this->assertSame('NO', $row['showemail'], 'Spaltenvorgabe in powernews.sql');
        $this->assertSame('', (new \user())->adduser('adminneu', 'adminneu@example.org', '', 'NO'));
        $this->assertSame('NO', $this->showemail('adminneu'));
    }

    #[Test]
    public function the_author_becomes_a_mail_link_only_when_chosen(): void
    {
        $withLink = $this->insertTestUser('verlinkt', 'verlinkt@example.org', 'x', 'Activated', 'YES');
        $without = $this->insertTestUser('privat', 'privat@example.org', 'x', 'Activated', 'NO');
        $news = new \pn_news();

        $this->assertSame('<a href="mailto:verlinkt@example.org">verlinkt</a>', $news->getauthor($withLink));
        $this->assertSame('privat', $news->getauthor($without));
    }

    private function showemail(string $nickname): string
    {
        global $pn_handler;

        $row = mysqli_fetch_assoc(pn_query_by_string($pn_handler, 'SELECT showemail FROM pn_users WHERE nickname = ?', $nickname));

        return (string) ($row['showemail'] ?? '');
    }
}
