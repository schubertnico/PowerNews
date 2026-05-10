<?php
declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                         */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

// Validierte Parameter
$search = pn_get_string('search', 10);
$searchin = pn_validate_whitelist($_GET['searchin'] ?? '', ['nickname', 'email', 'id'], 'nickname');
$searchstring = pn_get_string('searchstring', 250);
$current = pn_validate_int_range($_GET['current'] ?? 0, 0, PHP_INT_MAX, 0);

if ($pnadmin['canreadusers'] == 'YES') {
    if ($search === 'YES') {
        if ($searchstring === '') {
            ?>
            <div class="alert alert-warning" role="alert">
                <?php echo L_USR_SEARCHSTRINGNEEDED; ?>
                <div class="mt-2"><a href="index.php?page=users&amp;subpage=search" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zum Suchformular</a></div>
            </div>
            <?php
        } else {
            $searchuser = new user();
            ?>
            <nav aria-label="Seitennavigation oben" class="mb-3">
                <ul class="pagination pagination-sm mb-0 flex-wrap"><?php $searchuser->listsearchpages($searchin, $searchstring); ?></ul>
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
                        <?php $searchuser->searchuser($searchin, $searchstring, $current); ?>
                    </tbody>
                </table>
            </div>

            <nav aria-label="Seitennavigation unten" class="mb-3">
                <ul class="pagination pagination-sm mb-0 flex-wrap"><?php $searchuser->listsearchpages($searchin, $searchstring); ?></ul>
            </nav>
            <p class="pn-help mb-0"><?php echo L_USR_SHOWUSR_DESC; ?></p>
            <?php
        }
    } else {
        ?>
      <form action="./" method="get" novalidate>
          <input type="hidden" name="page" value="users">
          <input type="hidden" name="subpage" value="search">
          <input type="hidden" name="search" value="YES">

          <fieldset>
              <legend class="h6"><?php echo L_USR_SEARCHUSR; ?></legend>

              <div class="mb-3">
                  <label for="pn_searchin" class="form-label fw-bold"><?php echo L_USR_SEARCHFIELD; ?></label>
                  <select class="form-select" name="searchin" id="pn_searchin" aria-describedby="pn_searchin_help">
                      <option value="nickname"><?php echo L_USR_NICKNAME; ?></option>
                      <option value="email"><?php echo L_USR_EMAIL; ?></option>
                      <option value="id"><?php echo L_USR_USRID; ?></option>
                  </select>
                  <div id="pn_searchin_help" class="form-text"><?php echo L_USR_SEARCHFIELD_DESC; ?></div>
              </div>

              <div class="mb-3">
                  <label for="pn_searchstring" class="form-label fw-bold"><?php echo L_USR_SEARCHSTRING; ?></label>
                  <input class="form-control" name="searchstring" id="pn_searchstring" maxlength="250" required aria-describedby="pn_searchstring_help">
                  <div id="pn_searchstring_help" class="form-text"><?php echo L_USR_SEARCHSTRING_DESC; ?></div>
              </div>

              <button type="submit" class="btn btn-primary"><?php echo L_USR_SEARCHUSR; ?></button>
          </fieldset>
      </form>
      <?php
    }
} else {
    ?><div class="alert alert-danger mb-0" role="alert"><?php echo L_ALL_ACCESSDENIED; ?></div><?php
}
?>
