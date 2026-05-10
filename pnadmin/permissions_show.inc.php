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

if ($pnadmin['canreadpermissions'] == 'YES') {
    ?>
    <div class="table-responsive">
        <table class="table table-striped pn-admin-table align-middle">
            <thead>
                <tr>
                    <th style="min-width: 150px"><?php echo L_PERM_NICK; ?></th>
                    <th><?php echo L_PERM_PERMISSIONS; ?></th>
                </tr>
            </thead>
            <tbody>
<?php
                $permissions = new permissions();
                $permissions->listpermissions();
?>
            </tbody>
        </table>
    </div>
    <p class="pn-help mb-0"><?php echo L_PERM_SHOW_DESC; ?></p>
    <?php
} else {
    ?><div class="alert alert-danger mb-0" role="alert"><?php echo L_ALL_ACCESSDENIED; ?></div><?php
}
?>
