<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

/* Web-Installer: Schritt 3 (Website) */

use PowerNews\Installer\FormValidator;
use PowerNews\Installer\Html;
use PowerNews\LocalConfig;

/**
 * @param array<string, string> $errors
 * @param array<string, string> $old
 * @param array{type: string, message: string}|null $notice Ergebnis des Verbindungstests
 */
return static function (string $csrf, array $errors, string $message, array $old, ?array $notice): void {
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
      ], 'PowerNews verschickt E-Mails über die PHP-Funktion mail() Ihres Hosters. Nehmen Sie eine Adresse Ihrer eigenen Domain, z. B. noreply@ihre-domain.de – sonst lehnen viele Mailserver die Nachrichten ab.'); ?>

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
    <footer class="card-footer d-flex justify-content-between">
      <a class="btn btn-outline-secondary" href="install.php?step=2" id="btn-back">Zurück</a>
      <button type="submit" class="btn btn-primary" id="btn-website-next">Weiter zum Administrator</button>
    </footer>
  </section>
</form>
<?php
};
