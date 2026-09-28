<?php

declare(strict_types=1);

/**
 * Formatiert einen Zeitpunkt mit pn_format_date() unter einer bestimmten Sprachdatei.
 * Sprachkonstanten lassen sich in einem PHP-Prozess nur einmal definieren, deshalb läuft
 * jede Sprache in einem eigenen Unterprozess:
 *   php format-date.php <sprache> <zeitstempel> <format>
 */

date_default_timezone_set('Europe/Berlin');

[, $language, $timestamp, $format] = $argv + [null, 'english', '0', 'd.m.Y'];

require __DIR__ . '/../../pninc/lang/' . basename((string) $language) . '.php';
require __DIR__ . '/../../pninc/functions.inc.php';

echo pn_format_date((int) $timestamp, (string) $format);
