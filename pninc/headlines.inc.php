<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

$pn_news = new pn_news();
$pn_news->headlines(((isset($_GET['catid']) && trim((string) $_GET['catid']) !== '') ? intval($_GET['catid']) : 0), ((isset($current) && trim($current) !== '') ? trim($current) : ''));
