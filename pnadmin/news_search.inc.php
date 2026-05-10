<?php
declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                         */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

// Validierte Parameter
$search = pn_get_string('search', 10);
$searchin = pn_validate_whitelist($_GET['searchin'] ?? '', ['title', 'text', 'id', 'moretext'], 'title');
$searchstring = pn_get_string('searchstring', 250);
$current = pn_validate_int_range($_GET['current'] ?? 0, 0, PHP_INT_MAX, 0);

if ($pnadmin['canreadnews'] == 'YES') {
    if ($search === 'YES') {
        if ($searchstring === '') {
            ?>
            <div class="alert alert-warning" role="alert">
                <?php echo L_NEWS_SEARCHSTRINGNEEDED; ?>
                <div class="mt-2"><a href="index.php?page=news&amp;subpage=search" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zum Suchformular</a></div>
            </div>
            <?php
        } else {
            $searchnews = new news();
            ?>
            <nav aria-label="Seitennavigation oben" class="mb-3">
                <ul class="pagination pagination-sm mb-0 flex-wrap"><?php $searchnews->listsearchpages($searchin, $searchstring); ?></ul>
            </nav>

            <div class="table-responsive">
                <table class="table table-striped table-hover pn-admin-table align-middle">
                    <thead>
                        <tr>
                            <th><?php echo L_NEWS_DATE; ?></th>
<?php if ($pnconfig['categories'] == 'YES') { ?>
                            <th><?php echo L_NEWS_CATEGORY; ?></th>
<?php } ?>
                            <th><?php echo L_NEWS_TITLE; ?></th>
                            <th class="text-center"><?php echo L_NEWS_STATUS; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $searchnews->searchnews($searchin, $searchstring, $current); ?>
                    </tbody>
                </table>
            </div>

            <nav aria-label="Seitennavigation unten" class="mb-3">
                <ul class="pagination pagination-sm mb-0 flex-wrap"><?php $searchnews->listsearchpages($searchin, $searchstring); ?></ul>
            </nav>
            <p class="pn-help mb-0"><?php echo L_NEWS_SHOW_DESC; ?></p>
            <?php
        }
    } else {
        ?>
      <form action="./" method="get" novalidate>
          <input type="hidden" name="page" value="news">
          <input type="hidden" name="subpage" value="search">
          <input type="hidden" name="search" value="YES">

          <fieldset>
              <legend class="h6"><?php echo L_NEWS_SEARCHNEWS; ?></legend>

              <div class="mb-3">
                  <label for="pn_searchin" class="form-label fw-bold"><?php echo L_NEWS_SEARCHFIELD; ?></label>
                  <select class="form-select" name="searchin" id="pn_searchin" aria-describedby="pn_searchin_help">
                      <option value="title"><?php echo L_NEWS_TITLE; ?></option>
                      <option value="text"><?php echo L_NEWS_TEXT; ?></option>
                      <option value="id"><?php echo L_NEWS_NEWSID; ?></option>
<?php if ($pnconfig['moretext'] == 'YES') { ?>
                      <option value="moretext"><?php echo L_NEWS_LONGTEXT; ?></option>
<?php } ?>
                  </select>
                  <div id="pn_searchin_help" class="form-text"><?php echo L_NEWS_SEARCHFIELD_DESC; ?></div>
              </div>

              <div class="mb-3">
                  <label for="pn_searchstring" class="form-label fw-bold"><?php echo L_NEWS_SEARCHSTRING; ?></label>
                  <input class="form-control" name="searchstring" id="pn_searchstring" maxlength="250" required aria-describedby="pn_searchstring_help">
                  <div id="pn_searchstring_help" class="form-text"><?php echo L_NEWS_SEARCHSTRING_DESC; ?></div>
              </div>

              <button type="submit" class="btn btn-primary"><?php echo L_NEWS_SEARCHNEWS; ?></button>
          </fieldset>
      </form>
      <?php
    }
} else {
    ?><div class="alert alert-danger mb-0" role="alert"><?php echo L_ALL_ACCESSDENIED; ?></div><?php
}
?>
