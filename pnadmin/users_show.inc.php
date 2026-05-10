<?php

/* PowerNews is a PHP and mySQL based newsscript - www.powerscripts.org */
/* Copyright (C) 2001-2026 PowerScripts                                 */

/* This program is free software; you can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 2 of the License, or    */
/* (at your option) any later version.                                  */

/* This program is distributed in the hope that it will be useful,      */
/* but WITHOUT ANY WARRANTY; without even the implied warranty of       */
/* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        */
/* GNU General Public License for more details.                         */

/* You should have received a copy of the GNU General Public License    */
/* along with this program; if not, write to the Free Software          */
/* Foundation, Inc., 59 Temple Place, Suite 330, Boston,                */
/* MA  02111-1307  USA                                                  */

if ($pnadmin['canreadusers'] == 'YES') {
    $listusers = new user();
    if (!isset($_GET['current'])) {
        $_GET['current'] = '0';
    }
    ?>
    <nav aria-label="Seitennavigation oben" class="mb-3">
        <ul class="pagination pagination-sm mb-0 flex-wrap"><?php $listusers->listpages(); ?></ul>
    </nav>

    <div class="table-responsive">
        <table class="table table-striped table-hover pn-admin-table align-middle">
            <thead>
                <tr>
                    <th><?php echo L_USR_NICKNAME; ?></th>
                    <th><?php echo L_USR_EMAIL; ?></th>
                    <th class="text-center"><?php echo L_USR_SHOWEMAIL; ?></th>
                    <th class="text-center"><?php echo L_USR_ADMIN; ?></th>
                    <th class="text-center"><?php echo L_USR_STATUS; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $listusers->listusers((int) $_GET['current']); ?>
            </tbody>
        </table>
    </div>

    <nav aria-label="Seitennavigation unten" class="mb-3">
        <ul class="pagination pagination-sm mb-0 flex-wrap"><?php $listusers->listpages(); ?></ul>
    </nav>

    <p class="pn-help mb-0"><?php echo L_USR_SHOWUSR_DESC; ?></p>
    <?php
} else {
    ?><div class="alert alert-danger mb-0" role="alert"><?php echo L_ALL_ACCESSDENIED; ?></div><?php
}
?>
