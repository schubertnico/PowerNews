<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

/* Web-Installer: gesperrt (HTTP 403) */

use PowerNews\Installer\Html;
use PowerNews\Installer\InstallState;
use PowerNews\LocalConfig;

return static function (string $reason, string $lockFile): void {
    $text = match ($reason) {
        InstallState::REASON_LOCK_FILE => 'Die Installation wurde bereits abgeschlossen (Sperrdatei ' . $lockFile . ' vorhanden).',
        InstallState::REASON_LOCAL_CONFIG => 'Es gibt bereits ' . LocalConfig::RELATIVE_PATH . '. Damit eine bestehende Installation nicht überschrieben wird, startet der Installer nicht.',
        InstallState::REASON_DATABASE => 'In der konfigurierten Datenbank ist PowerNews bereits eingerichtet.',
        default => 'Per Umgebungsvariablen ist eine Datenbank eingerichtet, die gerade nicht erreichbar ist. Aus Sicherheitsgründen startet der Installer in diesem Zustand nicht.',
    };
    ?>
<div class="alert alert-warning" role="alert" id="installer-locked" data-reason="<?php echo Html::escape($reason); ?>">
  <strong>Der Installer ist gesperrt.</strong><br>
  <?php echo Html::escape($text); ?>
</div>

<section class="card shadow-sm mb-4">
  <div class="card-body">
    <p id="locked-delete-hint">
      <strong>Bitte löschen Sie die Datei <code>install.php</code> vom Server.</strong>
      Für den laufenden Betrieb wird sie nicht gebraucht.
    </p>
    <p class="small text-body-secondary mb-0">
      Bestehende Tabellen verändert der Installer nie. Für ein Update ist er nicht nötig – dafür gibt es
      <code>update.php</code> (siehe <code>INSTALLATION.md</code>, Abschnitt „Update von 3.11 auf 3.12“).
      Wirklich neu installieren? Dann legen Sie eine Datensicherung an, entfernen
      <code><?php echo Html::escape(LocalConfig::RELATIVE_PATH); ?></code> und die Sperrdatei
      (<?php echo implode(' bzw. ', array_map(static fn (string $file): string => '<code>' . Html::escape($file) . '</code>', InstallState::LOCK_FILES)); ?>)
      und verwenden eine leere Datenbank.
    </p>
  </div>
  <footer class="card-footer">
    <a class="btn btn-primary" href="index.php" id="link-frontend">Zur Startseite</a>
    <a class="btn btn-outline-secondary" href="pnadmin/" id="link-admin">Zum Adminbereich</a>
  </footer>
</section>
<?php
};
