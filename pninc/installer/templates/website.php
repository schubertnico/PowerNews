<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

/* Web-Installer: Schritt 3 (Website und E-Mail-Versand) */

use PowerNews\Installer\FormValidator;
use PowerNews\Installer\Html;
use PowerNews\Installer\SmtpCheck;
use PowerNews\Installer\Wizard;
use PowerNews\LocalConfig;
use PowerNews\Mailer;

/**
 * @param array<string, string> $errors
 * @param array<string, string> $old
 * @param array{type: string, message: string}|null $notice Ergebnis des Verbindungstests
 * @param array{type: string, message: string}|null $mailTest Ergebnis der Test-Mail
 * @param bool $passwordStored ein SMTP-Passwort liegt bereits in der Sitzung
 * @param string $testRecipient E-Mail-Adresse des Administrators, falls Schritt 4 schon erledigt ist
 */
return static function (
    string $csrf,
    array $errors,
    string $message,
    array $old,
    ?array $notice,
    ?array $mailTest,
    bool $passwordStored,
    string $testRecipient,
): void {
    $passwordHelp = 'Wird genau so übernommen, wie Sie es eingeben, und nicht wieder angezeigt.'
        . ($passwordStored ? ' Leer lassen, um das bereits eingegebene Passwort zu behalten.' : '');
    $recipientText = $testRecipient !== ''
        ? 'an die E-Mail-Adresse des Administrators (' . $testRecipient . ')'
        : 'an die Absenderadresse';
    ?>
<?php echo $notice !== null ? Html::alert($notice['message'], $notice['type'], 'db-server-info') : ''; ?>

<form method="post" action="install.php?step=3" id="form-website" novalidate>
  <?php echo Html::csrfField($csrf); ?>
  <input type="hidden" name="action" value="website">

  <section class="card shadow-sm mb-4">
    <header class="card-header bg-secondary-subtle">
      <h2 class="h5 mb-0">Ihre Website</h2>
    </header>
    <div class="card-body">
      <?php echo Html::alert($message, 'danger', 'installer-message'); ?>

      <?php echo Html::input('site_url', 'Adresse der Website (URL)', $old['site_url'] ?? '', $errors, [
          'type' => 'url',
          'required' => true,
          'maxlength' => FormValidator::URL_MAX,
          'spellcheck' => 'false',
      ], 'Vorbelegt mit der Adresse, unter der Sie den Installer gerade aufrufen – bitte prüfen. Sie steht in den Links der E-Mails (Registrierung, Passwort) und muss mit http:// oder https:// beginnen.'); ?>

      <?php echo Html::input('site_email', 'Absenderadresse für E-Mails', $old['site_email'] ?? '', $errors, [
          'type' => 'email',
          'required' => true,
          'maxlength' => FormValidator::EMAIL_MAX,
          'autocomplete' => 'email',
      ], 'Absender (From) aller Mails von PowerNews. Nehmen Sie eine Adresse Ihrer eigenen Domain, z. B. noreply@ihre-domain.de – sonst lehnen viele Mailserver die Nachrichten ab. Beim Versand über einen SMTP-Server muss sie meist zum Postfach passen.'); ?>

      <?php echo Html::select(
          'site_language',
          'Sprache',
          LocalConfig::LANGUAGES,
          $old['site_language'] ?? LocalConfig::DEFAULT_LANGUAGE,
          $errors,
          'Gilt für die Besucherseiten und den Adminbereich. Später in pninc/config.local.php änderbar.',
      ); ?>

      <p class="small text-body-secondary mb-0" id="website-later-hint">
        Weitere Einstellungen (Kommentare, News einsenden, Datumsformat …) nehmen Sie nach der Installation
        im Adminbereich unter „Konfiguration“ vor.
      </p>
    </div>
  </section>

  <section class="card shadow-sm mb-4" id="mail-settings">
    <header class="card-header bg-secondary-subtle">
      <h2 class="h5 mb-0">E-Mail-Versand <span class="badge text-bg-light border align-middle">optional</span></h2>
    </header>
    <div class="card-body">
      <?php echo $mailTest !== null ? Html::alert($mailTest['message'], $mailTest['type'], 'smtp-test-result') : ''; ?>

      <p class="small text-body-secondary" id="mail-intro">
        PowerNews verschickt E-Mails bei der Registrierung, für „Passwort vergessen“ und wenn Sie im Adminbereich
        Benutzer anlegen. Ab Werk übernimmt das die PHP-Funktion mail() Ihres Servers – das klappt bei den meisten
        Hostern ohne weitere Angaben. Kommen die Mails nicht an oder verlangt Ihr Hoster eine Anmeldung, wählen Sie
        „SMTP-Server“.
      </p>

      <?php echo Html::select(
          'mail_transport',
          'Versand über',
          SmtpCheck::TRANSPORTS,
          $old['mail_transport'] ?? Mailer::TRANSPORT_MAIL,
          $errors,
          'Alle Werte lassen sich später in pninc/config.local.php ändern.',
      ); ?>

      <fieldset class="border rounded p-3 mb-3" id="smtp-fields">
        <legend class="float-none w-auto px-2 fs-6 fw-semibold mb-0">SMTP-Server</legend>
        <p class="small mb-3" id="smtp-help">Die Angaben finden Sie im Kundenmenü Ihres Hosters beim E-Mail-Postfach.</p>

        <div class="row">
          <div class="col-md-8">
            <?php echo Html::input('smtp_host', 'SMTP-Server (Postausgang)', $old['smtp_host'] ?? '', $errors, [
                'maxlength' => FormValidator::HOST_MAX,
                'spellcheck' => 'false',
                'autocomplete' => 'off',
                'placeholder' => 'smtp.ihr-hoster.de',
            ], 'Bei Verschlüsselung den Namen aus dem Zertifikat verwenden, nicht „localhost“. Nur gefragt, wenn oben „SMTP-Server“ gewählt ist.'); ?>
          </div>
          <div class="col-md-4">
            <?php echo Html::input('smtp_port', 'Port', $old['smtp_port'] ?? '', $errors, [
                'type' => 'number',
                'min' => 1,
                'max' => 65535,
                'inputmode' => 'numeric',
                'data-default-ports' => json_encode(Mailer::DEFAULT_PORTS, JSON_THROW_ON_ERROR),
            ], 'Leer = üblicher Port der Verschlüsselung.'); ?>
          </div>
        </div>

        <?php echo Html::select(
            'smtp_encryption',
            'Verschlüsselung',
            SmtpCheck::ENCRYPTIONS,
            $old['smtp_encryption'] ?? Mailer::ENCRYPTION_STARTTLS,
            $errors,
            'Ohne Verschlüsselung gehen Passwort und E-Mails im Klartext durchs Netz – das passt nur für einen Mailserver auf demselben Rechner oder im internen Netz.',
        ); ?>

        <div class="row">
          <div class="col-md-6">
            <?php echo Html::input('smtp_user', 'Benutzername', $old['smtp_user'] ?? '', $errors, [
                'maxlength' => FormValidator::SMTP_USER_MAX,
                'autocomplete' => 'off',
                'spellcheck' => 'false',
            ], 'Meist die vollständige E-Mail-Adresse des Postfachs. Leer lassen, wenn der Server keine Anmeldung verlangt.'); ?>
          </div>
          <div class="col-md-6">
            <?php echo Html::input('smtp_password', 'Passwort', '', $errors, [
                'type' => 'password',
                'maxlength' => FormValidator::SMTP_PASSWORD_MAX,
                'autocomplete' => 'new-password',
            ], $passwordHelp); ?>
          </div>
        </div>
      </fieldset>

      <p class="small text-body-secondary mb-0" id="smtp-test-hint">
        „Test-Mail senden“ speichert die Angaben und schickt eine Nachricht <?php echo Html::escape($recipientText); ?>.
      </p>
    </div>
  </section>

  <div class="d-flex flex-wrap justify-content-between gap-2 mb-4">
    <a class="btn btn-outline-secondary" href="install.php?step=2" id="btn-back">Zurück</a>
    <!-- „Weiter“ steht im Quelltext zuerst: Die Eingabetaste löst so nie die Test-Mail aus. -->
    <div class="d-flex flex-row-reverse flex-wrap gap-2">
      <button type="submit" class="btn btn-primary" id="btn-website-next">Weiter zum Administrator</button>
      <button type="submit" name="action" value="<?php echo Html::escape(Wizard::ACTION_MAIL_TEST); ?>" class="btn btn-outline-primary" id="btn-smtp-test">Test-Mail senden</button>
    </div>
  </div>
</form>
<?php
};
