<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

/*
 * Gemeinsame Hilfsfunktionen für Frontend (pninc/) und Administration (pnadmin/).
 * Wird von beiden functions.inc.php per require_once geladen.
 */

/**
 * Ersetzt Platzhalter wie {TITLE} in einem Template.
 *
 * Alle Platzhalter werden in einem Durchgang ersetzt (strtr). Nutzerinhalte werden damit
 * weder als Regex-Rückverweis ausgewertet („$10“, „\1“ bleiben erhalten) noch ein zweites
 * Mal nach Platzhaltern durchsucht (ein Titel „{TEXT}“ bleibt wörtlich stehen).
 *
 * @param array<string, string|int> $values Platzhaltername ohne Klammern => Wert
 */
function pn_template_fill(string $template, array $values): string
{
    $pairs = [];

    foreach ($values as $name => $value) {
        $pairs['{' . $name . '}'] = (string) $value;
    }

    return strtr($template, $pairs);
}
