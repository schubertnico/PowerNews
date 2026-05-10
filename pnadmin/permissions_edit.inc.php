<?php
declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                         */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

// Validierte Parameter
$userid = pn_get_id('userid');
$edit = pn_get_string('edit', 10);

if ($pnadmin['canreadpermissions'] == 'YES' && $pnadmin['canwritepermissions'] == 'YES') {

    if ($userid === 0) {
        ?>
        <div class="alert alert-info" role="alert">
            <?php echo L_PERM_CHOOSEADMIN; ?>
            <div class="mt-2"><a href="index.php?page=permissions&amp;subpage=show" class="btn btn-sm btn-primary">Zur&uuml;ck</a></div>
        </div>
        <?php
    } else {
        $user = new user();
        $error = $user->checkuser($userid);
        $permissions = new permissions();

        if ($error !== '' && $error !== '0') {
            ?>
            <div class="alert alert-danger" role="alert">
                <?php echo pnadmin_escape($error); ?>
                <div class="mt-2"><a href="index.php?page=permissions&amp;subpage=show" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck</a></div>
            </div>
            <?php
        } else {
            if ($edit === 'YES') {
                $delete = pn_validate_yesno($_POST['delete'] ?? '');
                $permsData = PermissionsData::fromPost();
                $error = $permissions->editpermissions($userid, $permsData, $delete);

                if ($error !== '' && $error !== '0') {
                    ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo pnadmin_escape($error); ?>
                        <div class="mt-2"><a href="index.php?page=permissions&amp;subpage=edit&amp;userid=<?php echo pn_int($userid); ?>" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zum Formular</a></div>
                    </div>
                    <?php
                } else {
                    if ($delete === 'YES') {
                        ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo L_PERM_PERMISSIONSDELETED; ?>
                            <div class="mt-2"><a href="index.php?page=permissions&amp;subpage=show" class="btn btn-sm btn-success">Zur&uuml;ck zur Liste</a></div>
                        </div>
                        <?php
                    } else {
                        ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo L_PERM_PERMISSIONSEDITED; ?>
                            <div class="mt-2"><a href="index.php?page=permissions&amp;subpage=show" class="btn btn-sm btn-success">Zur&uuml;ck zur Liste</a></div>
                        </div>
                        <?php
                    }
                }
            } else {
                $data = $permissions->getdata($userid);
                ?>
          <form action="index.php?page=permissions&amp;subpage=edit&amp;edit=YES&amp;userid=<?php echo pn_int($userid); ?>" method="post" novalidate>
              <fieldset>
                  <legend class="h6"><?php echo L_PERM_EDITPERMISSIONS; ?></legend>

                  <div class="pn-danger-action mb-3">
                      <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="delete" value="YES" id="pn_delete" aria-describedby="pn_delete_help">
                          <label class="form-check-label fw-bold text-danger" for="pn_delete"><?php echo L_PERM_DELETE; ?></label>
                          <div id="pn_delete_help" class="form-text"><strong>Achtung:</strong> <?php echo L_PERM_DELETE_DESC; ?></div>
                      </div>
                  </div>

                  <div class="mb-3">
                      <label class="form-label fw-bold"><?php echo L_PERM_NICK; ?></label>
                      <div class="form-control-plaintext"><?php echo pnadmin_escape($data['nickname']); ?></div>
                      <div class="form-text"><?php echo L_PERM_NICK_DESC; ?></div>
                  </div>

                  <div class="mb-3">
                      <label class="form-label fw-bold"><?php echo L_PERM_PERMISSIONS; ?></label>
                      <div class="form-text mb-2"><?php echo L_PERM_PERMISSIONS_DESC; ?></div>
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
                                      <td class="text-center"><input class="form-check-input" type="checkbox" name="canreadtemplates" value="YES" <?php if ($data['canreadtemplates'] == 'YES') { echo 'checked'; } ?> aria-label="Templates lesen"></td>
                                      <td class="text-center"><input class="form-check-input" type="checkbox" name="canwritetemplates" value="YES" <?php if ($data['canwritetemplates'] == 'YES') { echo 'checked'; } ?> aria-label="Templates schreiben"></td>
                                  </tr>
                                  <tr>
                                      <td><?php echo L_PERM_CONFIG; ?></td>
                                      <td class="text-center"><input class="form-check-input" type="checkbox" name="canreadconfig" value="YES" <?php if ($data['canreadconfig'] == 'YES') { echo 'checked'; } ?> aria-label="Konfiguration lesen"></td>
                                      <td class="text-center"><input class="form-check-input" type="checkbox" name="canwriteconfig" value="YES" <?php if ($data['canwriteconfig'] == 'YES') { echo 'checked'; } ?> aria-label="Konfiguration schreiben"></td>
                                  </tr>
                                  <tr>
                                      <td><?php echo L_PERM_USER; ?></td>
                                      <td class="text-center"><input class="form-check-input" type="checkbox" name="canreadusers" value="YES" <?php if ($data['canreadusers'] == 'YES') { echo 'checked'; } ?> aria-label="Benutzer lesen"></td>
                                      <td class="text-center"><input class="form-check-input" type="checkbox" name="canwriteusers" value="YES" <?php if ($data['canwriteusers'] == 'YES') { echo 'checked'; } ?> aria-label="Benutzer schreiben"></td>
                                  </tr>
                                  <tr>
                                      <td><?php echo L_PERM_PERMISSIONS; ?></td>
                                      <td class="text-center"><input class="form-check-input" type="checkbox" name="canreadpermissions" value="YES" <?php if ($data['canreadpermissions'] == 'YES') { echo 'checked'; } ?> aria-label="Berechtigungen lesen"></td>
                                      <td class="text-center"><input class="form-check-input" type="checkbox" name="canwritepermissions" value="YES" <?php if ($data['canwritepermissions'] == 'YES') { echo 'checked'; } ?> aria-label="Berechtigungen schreiben"></td>
                                  </tr>
                                  <tr>
                                      <td><?php echo L_PERM_CATS; ?></td>
                                      <td class="text-center"><input class="form-check-input" type="checkbox" name="canreadcategories" value="YES" <?php if ($data['canreadcategories'] == 'YES') { echo 'checked'; } ?> aria-label="Kategorien lesen"></td>
                                      <td class="text-center"><input class="form-check-input" type="checkbox" name="canwritecategories" value="YES" <?php if ($data['canwritecategories'] == 'YES') { echo 'checked'; } ?> aria-label="Kategorien schreiben"></td>
                                  </tr>
                                  <tr>
                                      <td><?php echo L_PERM_NEWS; ?></td>
                                      <td class="text-center"><input class="form-check-input" type="checkbox" name="canreadnews" value="YES" <?php if ($data['canreadnews'] == 'YES') { echo 'checked'; } ?> aria-label="News lesen"></td>
                                      <td class="text-center"><input class="form-check-input" type="checkbox" name="canwritenews" value="YES" <?php if ($data['canwritenews'] == 'YES') { echo 'checked'; } ?> aria-label="News schreiben"></td>
                                  </tr>
                                  <tr>
                                      <td><?php echo L_PERM_COMMENTS; ?></td>
                                      <td class="text-center"><input class="form-check-input" type="checkbox" name="canreadcomments" value="YES" <?php if ($data['canreadcomments'] == 'YES') { echo 'checked'; } ?> aria-label="Kommentare lesen"></td>
                                      <td class="text-center"><input class="form-check-input" type="checkbox" name="canwritecomments" value="YES" <?php if ($data['canwritecomments'] == 'YES') { echo 'checked'; } ?> aria-label="Kommentare schreiben"></td>
                                  </tr>
                              </tbody>
                          </table>
                      </div>
                  </div>

                  <div class="d-flex gap-2">
                      <button type="submit" class="btn btn-primary"><?php echo L_PERM_EDITPERMISSIONS; ?></button>
                      <button type="reset" class="btn btn-outline-secondary"><?php echo L_ALL_RESETDATA; ?></button>
                  </div>
              </fieldset>
          </form>
          <?php
            }
        }
    }

} else {
    ?><div class="alert alert-danger mb-0" role="alert"><?php echo L_ALL_ACCESSDENIED; ?></div><?php
}
?>
