<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                         */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

$newsid = isset($_GET['newsid']) ? (int) $_GET['newsid'] : 0;
$text = $_POST['text'] ?? '';

if ($newsid > 0 && $text !== '') {
    $pn_news = new pn_news();
    $pn_news->postcomment($newsid, $text);
}

pn_cpi();
