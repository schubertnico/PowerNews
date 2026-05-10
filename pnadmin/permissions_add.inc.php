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

if ($pnadmin['canwritepermissions'] == 'YES') {

    if (isset($_GET['add']) && $_GET['add'] == 'YES') {
        if (!$_POST['user']) {
            ?>
            <div class="alert alert-warning" role="alert">
                <?php echo L_PERM_INSERTNICK; ?>
                <div class="mt-2"><a href="index.php?page=permissions&amp;subpage=add" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zum Formular</a></div>
            </div>
            <?php
        } else {
            $permissions = new permissions();
            $permsData = PermissionsData::fromPost();

            $error = $permissions->addpermissions($_POST['user'], $permsData);

            if ($error !== '' && $error !== '0') {
                ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error; ?>
                    <div class="mt-2"><a href="index.php?page=permissions&amp;subpage=add" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zum Formular</a></div>
                </div>
                <?php
            } else {
                ?>
                <div class="alert alert-success" role="alert">
                    <?php echo L_PERM_PERMISSIONADDED; ?>
                    <div class="mt-2"><a href="index.php?page=permissions&amp;subpage=show" class="btn btn-sm btn-success">Berechtigungen anzeigen</a></div>
                </div>
                <?php
            }
        }
    } else {
        ?>
      <form action="index.php?page=permissions&amp;subpage=add&amp;add=YES" method="post" novalidate>
          <fieldset>
              <legend class="h6"><?php echo L_PERM_ADDPERMISSIONS; ?></legend>

              <div class="mb-3">
                  <label for="pn_user" class="form-label fw-bold"><?php echo L_PERM_NICK; ?></label>
                  <input class="form-control" name="user" id="pn_user" maxlength="100" required aria-describedby="pn_user_help">
                  <div id="pn_user_help" class="form-text"><?php echo L_PERM_NICK_DESC; ?></div>
              </div>

              <div class="mb-3">
                  <label class="form-label fw-bold"><?php echo L_PERM_PERMISSIONS; ?></label>
                  <div class="table-responsive">
                      <table class="table table-sm align-middle">
                          <thead>
                              <tr>
                                  <th><?php echo L_PERM_SECTION; ?></th>
                                  <th class="text-center"><?php echo L_PERM_READ; ?></th>
                                  <th class="text-center"><?php echo L_PERM_WRITE; ?></th>
                              </tr>
                          </thead>
                          <tbody>
                              <tr>
                                  <td><?php echo L_PERM_TEMPLATES; ?></td>
                                  <td class="text-center"><input class="form-check-input" type="checkbox" name="canreadtemplates" value="YES" aria-label="Templates lesen"></td>
                                  <td class="text-center"><input class="form-check-input" type="checkbox" name="canwritetemplates" value="YES" aria-label="Templates schreiben"></td>
                              </tr>
                              <tr>
                                  <td><?php echo L_PERM_CONFIG; ?></td>
                                  <td class="text-center"><input class="form-check-input" type="checkbox" name="canreadconfig" value="YES" aria-label="Konfiguration lesen"></td>
                                  <td class="text-center"><input class="form-check-input" type="checkbox" name="canwriteconfig" value="YES" aria-label="Konfiguration schreiben"></td>
                              </tr>
                              <tr>
                                  <td><?php echo L_PERM_USER; ?></td>
                                  <td class="text-center"><input class="form-check-input" type="checkbox" name="canreadusers" value="YES" aria-label="Benutzer lesen"></td>
                                  <td class="text-center"><input class="form-check-input" type="checkbox" name="canwriteusers" value="YES" aria-label="Benutzer schreiben"></td>
                              </tr>
                              <tr>
                                  <td><?php echo L_PERM_PERMISSIONS; ?></td>
                                  <td class="text-center"><input class="form-check-input" type="checkbox" name="canreadpermissions" value="YES" aria-label="Berechtigungen lesen"></td>
                                  <td class="text-center"><input class="form-check-input" type="checkbox" name="canwritepermissions" value="YES" aria-label="Berechtigungen schreiben"></td>
                              </tr>
                              <tr>
                                  <td><?php echo L_PERM_CATS; ?></td>
                                  <td class="text-center"><input class="form-check-input" type="checkbox" name="canreadcategories" value="YES" aria-label="Kategorien lesen"></td>
                                  <td class="text-center"><input class="form-check-input" type="checkbox" name="canwritecategories" value="YES" aria-label="Kategorien schreiben"></td>
                              </tr>
                              <tr>
                                  <td><?php echo L_PERM_NEWS; ?></td>
                                  <td class="text-center"><input class="form-check-input" type="checkbox" name="canreadnews" value="YES" checked aria-label="News lesen"></td>
                                  <td class="text-center"><input class="form-check-input" type="checkbox" name="canwritenews" value="YES" checked aria-label="News schreiben"></td>
                              </tr>
                              <tr>
                                  <td><?php echo L_PERM_COMMENTS; ?></td>
                                  <td class="text-center"><input class="form-check-input" type="checkbox" name="canreadcomments" value="YES" checked aria-label="Kommentare lesen"></td>
                                  <td class="text-center"><input class="form-check-input" type="checkbox" name="canwritecomments" value="YES" checked aria-label="Kommentare schreiben"></td>
                              </tr>
                          </tbody>
                      </table>
                  </div>
              </div>

              <button type="submit" class="btn btn-primary"><?php echo L_PERM_ADDPERMISSIONS; ?></button>
          </fieldset>
      </form>
      <?php
    }

} else {
    ?><div class="alert alert-danger mb-0" role="alert"><?php echo L_ALL_ACCESSDENIED; ?></div><?php
}
?>
