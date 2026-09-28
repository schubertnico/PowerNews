<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

/* Web-Installer: Schritt 5 (Zusammenfassung und Abschluss) */

use PowerNews\Installer\Html;
use PowerNews\Installer\SmtpCheck;
use PowerNews\Installer\Wizard;
use PowerNews\LocalConfig;
use PowerNews\Mailer;

/**
 * @param array<string, string> $errors
 * @param string|null $lockTarget relativer Pfad der künftigen Sperrdatei, null = nicht beschreibbar
 */
return static function (string $csrf, array $errors, string $message, Wizard $wizard, bool $configWritable, ?string $lockTarget): void {
    $database = $wizard->database();
    $website = $wizard->website();
    $admin = $wizard->admin();

    if ($database === null || $website === null || $admin === null) {
        return;
    }

    $mail = $website['mail'];
    $smtp = $mail['transport'] === Mailer::TRANSPORT_SMTP;
    ?>
<p>Bitte prüfen Sie Ihre Angaben. „Jetzt installieren“ legt die Tabellen an, erstellt Ihr Administrator-Konto und sperrt den Installer.</p>

<?php echo Html::alert($message, 'danger', 'installer-message'); ?>

<div class="row g-3 mb-4" id="installer-summary">
  <div class="col-md-6">
    <section class="card shadow-sm h-100">
      <header class="card-header bg-secondary-subtle d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0">Datenbank</h2>
        <a class="small" href="install.php?step=2" id="edit-database">Ändern</a>
      </header>
      <dl class="card-body row align-content-start mb-0 small">
        <dt class="col-5">Server</dt>
        <dd class="col-7 text-break" id="summary-db-host"><?php echo Html::escape($database['host'] . ':' . $database['port']); ?></dd>
        <dt class="col-5">Datenbank</dt>
        <dd class="col-7 text-break" id="summary-db-name"><?php echo Html::escape($database['database']); ?></dd>
        <dt class="col-5">Benutzer</dt>
        <dd class="col-7 text-break" id="summary-db-user"><?php echo Html::escape($database['user']); ?></dd>
        <dt class="col-5">Erkannt</dt>
        <dd class="col-7 text-break mb-0" id="summary-db-server"><?php echo Html::escape($wizard->serverLabel()); ?></dd>
      </dl>
    </section>
  </div>
  <div class="col-md-6">
    <section class="card shadow-sm h-100">
      <header class="card-header bg-secondary-subtle d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0">Administrator</h2>
        <a class="small" href="install.php?step=4" id="edit-admin">Ändern</a>
      </header>
      <dl class="card-body row align-content-start mb-0 small">
        <dt class="col-5">Nickname</dt>
        <dd class="col-7 text-break" id="summary-admin-nickname"><?php echo Html::escape($admin['nickname']); ?></dd>
        <dt class="col-5">E-Mail</dt>
        <dd class="col-7 text-break mb-0" id="summary-admin-email"><?php echo Html::escape($admin['email']); ?></dd>
      </dl>
    </section>
  </div>
  <div class="col-12">
    <section class="card shadow-sm">
      <header class="card-header bg-secondary-subtle d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0">Website</h2>
        <a class="small" href="install.php?step=3" id="edit-website">Ändern</a>
      </header>
      <dl class="card-body row align-content-start mb-0 small">
        <dt class="col-sm-4">Adresse</dt>
        <dd class="col-sm-8 text-break" id="summary-site-url"><?php echo Html::escape($website['url']); ?></dd>
        <dt class="col-sm-4">Absender</dt>
        <dd class="col-sm-8 text-break" id="summary-site-email"><?php echo Html::escape($website['email']); ?></dd>
        <dt class="col-sm-4">Sprache</dt>
        <dd class="col-sm-8 mb-0" id="summary-site-language"><?php echo Html::escape(LocalConfig::LANGUAGES[$website['language']] ?? $website['language']); ?></dd>
      </dl>
    </section>
  </div>
  <div class="col-12">
    <section class="card shadow-sm" id="summary-mail">
      <header class="card-header bg-secondary-subtle d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0">E-Mail-Versand</h2>
        <a class="small" href="install.php?step=3#mail-settings" id="edit-mail">Ändern</a>
      </header>
      <dl class="card-body row align-content-start mb-0 small">
        <dt class="col-sm-4">Versand über</dt>
        <dd class="col-sm-8<?php echo $smtp ? '' : ' mb-0'; ?>" id="summary-mail-transport"><?php echo Html::escape(SmtpCheck::TRANSPORTS[$smtp ? Mailer::TRANSPORT_SMTP : Mailer::TRANSPORT_MAIL]); ?></dd>
<?php if ($smtp) { ?>
        <dt class="col-sm-4">Server</dt>
        <dd class="col-sm-8 text-break" id="summary-smtp-host"><?php echo Html::escape($mail['host'] . ':' . Mailer::effectivePort($mail['port'], $mail['encryption'])); ?></dd>
        <dt class="col-sm-4">Verschlüsselung</dt>
        <dd class="col-sm-8" id="summary-smtp-encryption"><?php echo Html::escape(SmtpCheck::ENCRYPTIONS[$mail['encryption']] ?? $mail['encryption']); ?></dd>
        <dt class="col-sm-4">Anmeldung</dt>
        <dd class="col-sm-8 text-break mb-0" id="summary-smtp-user"><?php echo Html::escape($mail['user'] !== '' ? 'als ' . $mail['user'] . ' (Passwort gespeichert)' : 'ohne Anmeldung'); ?></dd>
<?php } ?>
      </dl>
    </section>
  </div>
</div>

<?php if ($configWritable) { ?>
<div class="alert alert-info" role="note" id="config-mode-auto">
  Die Zugangsdaten für Datenbank und E-Mail-Versand werden in <code><?php echo Html::escape(LocalConfig::RELATIVE_PATH); ?></code> gespeichert.
</div>
<?php } else { ?>
<div class="alert alert-warning" role="note" id="config-mode-manual">
  Das Verzeichnis <code>pninc/</code> ist nicht beschreibbar. Nach der Installation bietet der Installer
  <code><?php echo Html::escape(LocalConfig::FILENAME); ?></code> zum Herunterladen an – Sie laden die Datei dann selbst
  (z. B. per FTP) nach <code>pninc/</code> hoch.
</div>
<?php } ?>

<?php if ($lockTarget === null) { ?>
<div class="alert alert-danger" role="alert" id="lock-mode-missing">
  Weder <code>pninc/</code> noch <code>logs/</code> ist beschreibbar – der Installer könnte sich nicht sperren.
  Bitte machen Sie <code>logs/</code> beschreibbar, bevor Sie installieren.
</div>
<?php } else { ?>
<p class="small text-body-secondary" id="lock-mode-info">
  Nach der Installation sperrt sich der Installer über die Datei <code><?php echo Html::escape($lockTarget); ?></code>.
</p>
<?php } ?>

<form method="post" action="install.php?step=5" id="form-finish" class="d-flex justify-content-between align-items-center mb-4">
  <?php echo Html::csrfField($csrf); ?>
  <a class="btn btn-outline-secondary" href="install.php?step=4" id="btn-back">Zurück</a>
  <button type="submit" name="action" value="finish" class="btn btn-success btn-lg" id="btn-install">Jetzt installieren</button>
</form>
<?php
};
