<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

/* Web-Installer: Schritt 1 (Systemprüfung) */

use PowerNews\Installer\Html;

/**
 * @param list<array{id: string, label: string, ok: bool, required: bool, detail: string}> $checks
 * @param array<string, string> $errors
 */
return static function (string $csrf, array $errors, string $message, array $checks, bool $allOk): void {
    ?>
<section class="card shadow-sm mb-4">
  <header class="card-header bg-secondary-subtle">
    <h2 class="h5 mb-0">Voraussetzungen des Servers</h2>
  </header>
  <div class="card-body">
    <p>
      Willkommen! Dieser Assistent richtet PowerNews in fünf Schritten ein: Er legt die Tabellen an,
      erstellt Ihr Administrator-Konto und speichert die Zugangsdaten in <code>pninc/config.local.php</code>.
      Zuerst prüft er, ob Ihr Server alle Voraussetzungen erfüllt. Eine Anleitung finden Sie in der
      <a href="README.html" target="_blank" rel="noopener noreferrer" id="link-readme">ReadMe</a>
      und in <code>INSTALLATION.md</code>.
    </p>

    <?php echo Html::alert($message, 'danger', 'installer-message'); ?>

    <ul class="list-group mb-3" id="requirements-list">
<?php
    foreach ($checks as $check) {
        if ($check['ok']) {
            $badge = '<span class="badge text-bg-success">erfüllt</span>';
        } elseif ($check['required']) {
            $badge = '<span class="badge text-bg-danger">nicht erfüllt</span>';
        } else {
            $badge = '<span class="badge text-bg-warning">Hinweis</span>';
        }
        ?>
      <li class="list-group-item d-flex justify-content-between align-items-start gap-3" id="check-<?php echo Html::escape($check['id']); ?>" data-ok="<?php echo $check['ok'] ? '1' : '0'; ?>">
        <div>
          <strong><?php echo Html::escape($check['label']); ?></strong>
          <span class="badge <?php echo $check['required'] ? 'text-bg-secondary' : 'text-bg-light border'; ?> ms-1"><?php echo $check['required'] ? 'Pflicht' : 'Optional'; ?></span>
          <div class="small text-body-secondary"><?php echo Html::escape($check['detail']); ?></div>
        </div>
        <?php echo $badge; ?>
      </li>
<?php
    }
    ?>
    </ul>
<?php if (!$allOk) { ?>
    <div class="alert alert-danger" role="alert" id="requirements-failed">
      Bitte beheben Sie die rot markierten Punkte. Danach
      <a class="alert-link" href="install.php?step=1" id="btn-requirements-reload">prüfen Sie erneut</a>.
    </div>
<?php } ?>
  </div>
  <footer class="card-footer d-flex justify-content-end">
    <form method="post" action="install.php?step=1" id="form-requirements">
      <?php echo Html::csrfField($csrf); ?>
      <button type="submit" name="action" value="requirements" class="btn btn-primary" id="btn-requirements-next"<?php echo $allOk ? '' : ' disabled'; ?>>
        Weiter zur Datenbank
      </button>
    </form>
  </footer>
</section>
<?php
};
