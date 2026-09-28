<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

$newsid = (int) ($_GET['newsid'] ?? 0);
$showcomments = (($_GET['showcomments'] ?? '') === 'YES');

$pn_news = new pn_news();
$pn_news->details($newsid);

if (($pnconfig['comments'] ?? 'NO') === 'YES' && ($pn_newsexist ?? 'NO') === 'YES' && $showcomments) {
    $pn_news->comments($newsid);
    $pn_news->commentform($newsid);
}

pn_cpi();
