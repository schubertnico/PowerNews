<?php
declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                         */
/* Copyright (c) 2001-2024 PowerScripts                                 */

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
            ?><center><a href="javascript:history.back()"><?php echo L_USR_SEARCHSTRINGNEEDED; ?></a></center><?php
        } else {
            ?>
        <center>
        <?php
              $searchuser = new user();
            $searchuser->listsearchpages($searchin, $searchstring);
            ?>
        <br><br>
        <table border="0" cellpadding="4" cellspacing="0">
        <tr><td>
        <b><?php echo L_USR_NICKNAME; ?></b>
        </td><td width="30">
        &nbsp;
        </td><td>
        <b><?php echo L_USR_EMAIL; ?></b>
        </td><td width="30">
        &nbsp;
        </td><td>
        <b><?php echo L_USR_SHOWEMAIL; ?></b>
        </td><td width="30">
        &nbsp;
        </td><td>
        <b><?php echo L_USR_ADMIN; ?></b>
        </td><td width="30">
        &nbsp;
        </td><td>
        <b><?php echo L_USR_STATUS; ?></b>
        </td></tr>
        <?php
              $searchuser->searchuser($searchin, $searchstring, $current);
            ?>
        </table>
        <br>
        <?php $searchuser->listsearchpages($searchin, $searchstring); ?>
        </center><br>
        <br>
        <small><?php echo L_USR_SHOWUSR_DESC; ?></small>
        <?php
        }
    } else {
        ?>
      <center>
      <form action="./" method="get">
      <input type="hidden" name="page" value="users">
      <input type="hidden" name="subpage" value="search">
      <input type="hidden" name="search" value="YES">
      <table border="0" cellpadding="4" cellspacing="0">
      <tr><td colspan="2" align="center">
      <b><?php echo L_USR_SEARCHUSR; ?></b>
      </td></tr>
      <tr><td>
      <b><?php echo L_USR_SEARCHFIELD; ?></b><br>
      <small class="info"><?php echo L_USR_SEARCHFIELD_DESC; ?></small>
      </td><td>
      <select name="searchin" size="1">
        <option value="nickname"><?php echo L_USR_NICKNAME; ?></option>
        <option value="email"><?php echo L_USR_EMAIL; ?></option>
        <option value="id"><?php echo L_USR_USRID; ?></option>
      </select>
      </td></tr>
      <tr><td>
      <b><?php echo L_USR_SEARCHSTRING; ?></b><br>
      <small class="info"><?php echo L_USR_SEARCHSTRING_DESC; ?></small>
      </td><td>
      <input name="searchstring" size="25" maxlength="250">
      </td></tr>
      <tr><td colspan="2" align="center">
      <input type="submit" value="<?php echo L_USR_SEARCHUSR; ?>">
      </td></tr>
      </table>
      </form>
      </center>
      <?php
    }
} else {
    ?><center><?php echo L_ALL_ACCESSDENIED; ?></center><?php
}
?>
