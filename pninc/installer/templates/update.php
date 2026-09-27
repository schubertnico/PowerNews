<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

/* update.php: Zustand der Datenbank und Update-Schritte */

use PowerNews\Installer\Html;
use PowerNews\Installer\ServerVersion;
use PowerNews\LocalConfig;

/**
 * @param list<array{id: string, label: string, detail: string, pending: bool}> $steps
 * @param list<array{id: string, label: string, ok: bool, message: string}>|null $results Ergebnis des letzten Laufs
 */
return static function (
    string $csrf,
    string $message,
    string $version,
    string $phpVersion,
    ?ServerVersion $server,
    string $configSource,
    array $steps,
    ?array $results,
): void {
    $pending = array_filter($steps, static fn (array $step): bool => $step['pending']);
    $sourceText = match ($configSource) {
        LocalConfig::SOURCE_FILE => LocalConfig::RELATIVE_PATH . ' – bleibt bei künftigen Updates erhalten.',
        LocalConfig::SOURCE_ENVIRONMENT => 'Umgebungsvariablen PN_DB_* – bleiben bei künftigen Updates erhalten.',
        default => 'Vorgaben aus pninc/config.inc.php. Legen Sie ' . LocalConfig::RELATIVE_PATH . ' an (siehe INSTALLATION.md), sonst gehen eigene Zugangsdaten beim nächsten Update verloren.',
    };
    ?>
<p>
  Diese Seite bringt die Datenbank einer bestehenden PowerNews-3.x-Installation auf den Stand der hochgeladenen Dateien.
  Jeder Schritt prüft selbst, ob er nötig ist, und lässt sich gefahrlos wiederholen. Legen Sie vorher eine Datensicherung an.
</p>

<?php echo Html::alert($message, 'danger', 'update-message'); ?>

<?php if ($results !== null) { ?>
<section class="card shadow-sm mb-4" id="update-results">
  <header class="card-header bg-secondary-subtle"><h2 class="h6 mb-0">Ergebnis</h2></header>
  <ul class="list-group list-group-flush">
<?php if ($results === []) { ?>
    <li class="list-group-item">Es war nichts zu tun.</li>
<?php } ?>
<?php foreach ($results as $result) { ?>
    <li class="list-group-item d-flex justify-content-between gap-3" id="result-<?php echo Html::escape($result['id']); ?>">
      <div><strong><?php echo Html::escape($result['label']); ?></strong><div class="small"><?php echo Html::escape($result['message']); ?></div></div>
      <span class="badge <?php echo $result['ok'] ? 'text-bg-success' : 'text-bg-danger'; ?> align-self-start"><?php echo $result['ok'] ? 'erledigt' : 'fehlgeschlagen'; ?></span>
    </li>
<?php } ?>
  </ul>
</section>
<?php } ?>

<section class="card shadow-sm mb-4" id="update-status">
  <header class="card-header bg-secondary-subtle"><h2 class="h6 mb-0">Installation</h2></header>
  <dl class="card-body row mb-0 small">
    <dt class="col-sm-4">Programmversion (Dateien)</dt>
    <dd class="col-sm-8" id="status-version"><?php echo Html::escape($version); ?></dd>
    <dt class="col-sm-4">PHP</dt>
    <dd class="col-sm-8" id="status-php"><?php echo Html::escape($phpVersion); ?></dd>
    <dt class="col-sm-4">Datenbankserver</dt>
    <dd class="col-sm-8" id="status-dbserver">
<?php if ($server === null) { ?>
      unbekannt
<?php } else { ?>
      <?php echo Html::escape($server->label()); ?>
      <span class="badge <?php echo $server->isSupported() ? 'text-bg-success' : 'text-bg-danger'; ?>"><?php echo $server->isSupported() ? 'geeignet' : 'zu alt – benötigt ' . Html::escape(ServerVersion::requirement()); ?></span>
<?php } ?>
    </dd>
    <dt class="col-sm-4">Zugangsdaten aus</dt>
    <dd class="col-sm-8 mb-0" id="status-config-source"><?php echo Html::escape($sourceText); ?></dd>
  </dl>
</section>

<section class="card shadow-sm mb-4" id="update-steps">
  <header class="card-header bg-secondary-subtle"><h2 class="h6 mb-0">Update-Schritte</h2></header>
  <ul class="list-group list-group-flush">
<?php foreach ($steps as $step) { ?>
    <li class="list-group-item d-flex justify-content-between gap-3" id="step-<?php echo Html::escape($step['id']); ?>" data-pending="<?php echo $step['pending'] ? '1' : '0'; ?>">
      <div><strong><?php echo Html::escape($step['label']); ?></strong><div class="small text-body-secondary"><?php echo Html::escape($step['detail']); ?></div></div>
      <span class="badge <?php echo $step['pending'] ? 'text-bg-warning' : 'text-bg-success'; ?> align-self-start"><?php echo $step['pending'] ? 'erforderlich' : 'erledigt'; ?></span>
    </li>
<?php } ?>
  </ul>
  <footer class="card-footer d-flex justify-content-between align-items-center">
    <a class="btn btn-outline-secondary" href="pnadmin/" id="link-admin">Zum Adminbereich</a>
<?php if ($pending !== []) { ?>
    <form method="post" action="update.php" id="form-update">
      <?php echo Html::csrfField($csrf); ?>
      <button type="submit" class="btn btn-primary" id="btn-update-run">Update ausführen</button>
    </form>
<?php } else { ?>
    <span class="text-success fw-semibold" id="update-uptodate">Die Datenbank ist auf dem aktuellen Stand.</span>
<?php } ?>
  </footer>
</section>

<p class="small text-body-secondary">
  Danach: <code>install.php</code> vom Server löschen, falls Sie sie mit hochgeladen haben. Diese Datei (<code>update.php</code>)
  kann bleiben – sie ist nur für Admins erreichbar.
</p>
<?php
};
