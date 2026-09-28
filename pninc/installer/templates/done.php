<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

/* Web-Installer: Installation abgeschlossen */

use PowerNews\Installer\Html;
use PowerNews\LocalConfig;

/**
 * @param array{config_written: bool, config_source: string, lock_file: string, admin_nickname: string, site_url: string} $done
 */
return static function (array $done, bool $configPresent, string $csrf): void {
    ?>
<div class="alert alert-success" role="status" id="installer-success">
  <strong>PowerNews ist installiert.</strong><br>
  Ihr Administrator-Konto <strong id="done-admin-nickname"><?php echo Html::escape($done['admin_nickname']); ?></strong> ist angelegt.
  Melden Sie sich im <a class="alert-link" href="pnadmin/" id="link-admin-inline">Adminbereich</a> mit diesem Nickname und Ihrem Passwort an.
</div>

<?php if ($done['config_written']) { ?>
<section class="card shadow-sm mb-4" id="config-written">
  <header class="card-header bg-secondary-subtle">
    <h2 class="h6 mb-0">Konfiguration gespeichert</h2>
  </header>
  <div class="card-body small">
    <p>
      Die Zugangsdaten stehen in <code><?php echo Html::escape(LocalConfig::RELATIVE_PATH); ?></code>. Die Datei ist per
      <code>.htaccess</code> vor direktem Abruf geschützt und hat – sofern der Server das zulässt – die Rechte <code>640</code> erhalten.
    </p>
    <p class="mb-0">Legen Sie eine Sicherungskopie der Datei an; bei einem Update wird sie nicht überschrieben.</p>
  </div>
</section>
<?php } elseif ($configPresent) { ?>
<div class="alert alert-success" role="status" id="config-present">
  <code><?php echo Html::escape(LocalConfig::RELATIVE_PATH); ?></code> wurde gefunden – PowerNews ist einsatzbereit.
</div>
<?php } else { ?>
<section class="card shadow-sm border-warning mb-4" id="config-manual">
  <header class="card-header bg-warning-subtle">
    <h2 class="h6 mb-0">Noch ein Schritt: <code><?php echo Html::escape(LocalConfig::FILENAME); ?></code> hochladen</h2>
  </header>
  <div class="card-body">
    <p>
      Der Installer durfte die Datei nicht selbst schreiben. Laden Sie sie herunter und legen Sie sie per FTP in das
      Verzeichnis <code>pninc/</code> (dort, wo auch <code>config.inc.php</code> liegt). Bis dahin kann PowerNews die
      Datenbank nicht erreichen. Die Datei enthält Ihr Datenbankpasswort – bitte nicht weitergeben.
    </p>
    <form method="post" action="install.php" class="mb-3" id="form-download-config">
      <?php echo Html::csrfField($csrf); ?>
      <button type="submit" name="action" value="download_config" class="btn btn-primary" id="btn-download-config">
        <?php echo Html::escape(LocalConfig::FILENAME); ?> herunterladen
      </button>
    </form>
    <label for="config-local-content" class="form-label fw-semibold">Oder Inhalt kopieren:</label>
    <textarea id="config-local-content" class="form-control font-monospace small" rows="18" readonly spellcheck="false"><?php echo Html::escape($done['config_source']); ?></textarea>
    <p class="small text-body-secondary mt-2 mb-0">
      Sobald die Datei auf dem Server liegt, <a href="install.php" id="btn-config-recheck">laden Sie diese Seite neu</a>.
    </p>
  </div>
</section>
<?php } ?>

<?php if ($done['lock_file'] !== '') { ?>
<p class="small text-body-secondary" id="lock-written">
  Der Installer ist gesperrt (Sperrdatei <code><?php echo Html::escape($done['lock_file']); ?></code>). Jeder weitere Aufruf von
  <code>install.php</code> endet mit „Installer gesperrt“.
</p>
<?php } else { ?>
<div class="alert alert-danger" role="alert" id="lock-not-written">
  <strong>Die Sperrdatei konnte nicht angelegt werden.</strong>
  Der Installer ist erst gesperrt, wenn <code><?php echo Html::escape(LocalConfig::RELATIVE_PATH); ?></code> auf dem Server liegt.
  Löschen Sie <code>install.php</code> deshalb sofort.
</div>
<?php } ?>

<div class="alert alert-warning" role="alert" id="delete-installer">
  <strong>Bitte löschen Sie jetzt die Datei <code>install.php</code> vom Server.</strong>
  Der Installer ist gesperrt, gehört aber nicht auf eine laufende Website. Das Verzeichnis <code>pninc/installer/</code>
  bleibt – es wird für <code>update.php</code> gebraucht und ist nicht von außen abrufbar.
</div>

<div class="d-flex flex-wrap gap-2 mb-4">
  <a class="btn btn-primary" href="pnadmin/" id="link-admin">Zum Adminbereich</a>
  <a class="btn btn-outline-secondary" href="index.php" id="link-frontend">Zur Startseite</a>
</div>
<?php
};
