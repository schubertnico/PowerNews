<?php

declare(strict_types=1);

/*
 * Prüfling für tests/Unit/LoadPathTest.php: eine Adminseite, die zwei Namen aus
 * pninc/functions.inc.php benutzt. Die Datei wird nur gelesen, nie ausgeführt.
 */

use PowerNews\LocalConfig;

// gemeldet: nur im Frontend definiert
echo pn_format_date(0, 'd.m.Y');
$template = new pn_template();

// nicht gemeldet: im Adminladeweg definiert, Methodenaufrufe, PHP-Funktionen
echo pnadmin_escape(pn_template_fill('{A}', ['A' => 'a']));
$template->pn_format_date();
echo strlen(LocalConfig::FILENAME) . \count([]);
$closure = static function () use ($template): string {
    return $template::class;
};
