<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

/* Web-Installer: Schritt 4 (Administrator) */

use PowerNews\Installer\FormValidator;
use PowerNews\Installer\Html;

/**
 * @param array<string, string> $errors
 * @param array<string, string> $old
 */
return static function (string $csrf, array $errors, string $message, array $old): void {
    ?>
<form method="post" action="install.php?step=4" id="form-admin" novalidate>
  <?php echo Html::csrfField($csrf); ?>
  <input type="hidden" name="action" value="admin">

  <section class="card shadow-sm mb-4">
    <header class="card-header bg-secondary-subtle">
      <h2 class="h5 mb-0">Ihr Administrator-Konto</h2>
    </header>
    <div class="card-body">
      <p>
        Mit diesem Konto verwalten Sie PowerNews. Angemeldet wird später im Adminbereich
        (<code>pnadmin/</code>) mit <strong>Nickname und Passwort</strong>.
      </p>

      <?php echo Html::alert($message, 'danger', 'installer-message'); ?>

      <?php echo Html::input('admin_nickname', 'Nickname', $old['admin_nickname'] ?? '', $errors, [
          'required' => true,
          'minlength' => FormValidator::NICKNAME_MIN,
          'maxlength' => FormValidator::NICKNAME_MAX,
          'autocomplete' => 'username',
          'spellcheck' => 'false',
      ], '3 bis 30 Zeichen: Buchstaben (auch Umlaute), Ziffern sowie . _ -'); ?>

      <?php echo Html::input('admin_email', 'E-Mail-Adresse', $old['admin_email'] ?? '', $errors, [
          'type' => 'email',
          'required' => true,
          'maxlength' => FormValidator::EMAIL_MAX,
          'autocomplete' => 'email',
      ], 'Ihre eigene Adresse, z. B. für „Passwort vergessen“. Sie wird nicht öffentlich angezeigt.'); ?>

      <div class="row">
        <div class="col-md-6">
          <?php echo Html::input('admin_password', 'Passwort', '', $errors, [
              'type' => 'password',
              'required' => true,
              'minlength' => FormValidator::PASSWORD_MIN,
              'maxlength' => FormValidator::PASSWORD_MAX_BYTES,
              'autocomplete' => 'new-password',
          ], 'Mindestens ' . FormValidator::PASSWORD_MIN . ' Zeichen. Gespeichert wird nur ein Hash (bcrypt).'); ?>
        </div>
        <div class="col-md-6">
          <?php echo Html::input('admin_password_confirm', 'Passwort wiederholen', '', $errors, [
              'type' => 'password',
              'required' => true,
              'minlength' => FormValidator::PASSWORD_MIN,
              'maxlength' => FormValidator::PASSWORD_MAX_BYTES,
              'autocomplete' => 'new-password',
          ]); ?>
        </div>
      </div>
    </div>
    <footer class="card-footer d-flex justify-content-between">
      <a class="btn btn-outline-secondary" href="install.php?step=3" id="btn-back">Zurück</a>
      <button type="submit" class="btn btn-primary" id="btn-admin-next">Weiter zur Zusammenfassung</button>
    </footer>
  </section>
</form>
<?php
};
