<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

$pn_user = new pn_user();
$page = $_GET['page'] ?? '';

switch ($page) {
    case 'logout':
        $pn_user->logout();
        break;
    case 'profile':
        $pn_user->profile();
        break;
    case 'senddata':
        $pn_user->senddata();
        break;
    case 'resetpassword':
        $pn_user->resetpassword();
        break;
    case 'login':
        $pn_user->login();
        break;
    default:
        $pn_user->register();
        break;
}

pn_cpi();
