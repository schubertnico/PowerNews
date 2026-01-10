<?PHP
/************************************************************************/
/* PowerNews is a PHP and mySQL based newsscript - www.powerscripts.org */
/* Copyright (C) 2001-2023 PowerScripts                                 */
/*                                                                      */
/* This program is free software; you can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 2 of the License, or    */
/* (at your option) any later version.                                  */
/*                                                                      */
/* This program is distributed in the hope that it will be useful,      */
/* but WITHOUT ANY WARRANTY; without even the implied warranty of       */
/* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        */
/* GNU General Public License for more details.                         */
/*                                                                      */
/* You should have received a copy of the GNU General Public License    */
/* along with this program; if not, write to the Free Software          */
/* Foundation, Inc., 59 Temple Place, Suite 330, Boston,                */
/* MA  02111-1307  USA                                                  */
/************************************************************************/

$startoutput = explode(" ", microtime());
// Check if phpheader file exists and include it
if (@file_exists("phpheader.inc.php")) {
  include(__DIR__ . "/phpheader.inc.php");
} else {
  echo "<center>File <b>phpheader.inc.php</b> wasn't found!</center>";
  exit;
}

if ($pn_config['acpuffer'] == TRUE) {
  //  echo '1';exit;
  ob_start("ob_gzhandler");
}
?>
<?PHP $psdesignscript = "PowerNews";
$psdesignversion = "3.00"; ?>
    <html>
    <head>
        <title><?PHP echo $psdesignscript . " " . $psdesignversion; ?> -- AdminCenter</title>
        <link rel="stylesheet" href="./poweradmin.css" type="text/css">
    </head>
    <noscript></noscript>
    <body bgcolor="#002040" topmargin="10" bottommargin="10" leftmargin="10" rightmargin="10" text="#ffffff"
          link="#B5C3D9" vlink="#858585" alink="#333366" marginwidth="0" marginheight="0">

    <table border="0" width="100%" cellpadding="4" cellspacing="1">
        <tr>
            <td width="500">
                <a href="./" class="logo"><img src="./powernews.gif" width="461" height="179" border="0"></a>
            </td>
            <td width="*" align="center" valign="center">
                <table border="1" cellpadding="4" cellspacing="0" width="100%" bordercolor="#6078A0">
                    <tr bgcolor="#001329">
                        <td align="center" valign="center">
                            <b><?PHP echo $psdesignscript . " " . $psdesignversion; ?> AdminCenter</b><br>
                        </td>
                    </tr>
                    <tr bgcolor="#001329">
                        <td align="center">

                            <table border="0" cellpadding="0" cellspacing="3" width="100%">
                                <tr>
                                    <td width="25%" valign="top">
                                        <small>
                                            &raquo; <a
                                                    href="index.php?page=templates"><?PHP echo L_MENU_TEMPLATES; ?></a><br>
                                            &raquo; <a href="index.php?page=users"><?PHP echo L_MENU_USERS; ?></a><br>
                                            &raquo; <a
                                                    href="index.php?page=permissions"><?PHP echo L_MENU_PERMISSIONS; ?></a><br>
                                            &raquo; <a
                                                    href="index.php?page=configuration"><?PHP echo L_MENU_CONFIG; ?></a><br>
                                            &raquo; <a
                                                    href="index.php?page=categories"><?PHP echo L_MENU_CATEGORIES; ?></a><br>
                                            &raquo; <a href="index.php?page=news"><?PHP echo L_MENU_NEWS; ?></a><br>
                                            <br>
                                            &raquo; <a href="index.php?page=other"><?PHP echo L_MENU_OTHER; ?></a><br>
                                        </small>
                                    </td>
                                    <td width="75%" valign="top">
                                        <small>
                                          <?PHP if (isset($_GET['page']) && $_GET['page'] && isset($individualmenus)) {
                                            $individualmenus->submenu($_GET['page']);
                                          } ?>
                                        </small>
                                    </td>
                                </tr>
                            </table>

                            <br>

                            <small>
                                <a href="../"><?PHP echo L_MENU_EXTERN; ?></a>
                            </small>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <br><br><br>

    <table border="0" cellpadding="4" cellspacing="1" width="100%" bgcolor="#6078A0">
        <tr bgcolor="#001329">
            <td valign="bottom" align="left">
                <b class="small">&raquo; <?PHP
                  if (isset($pnloggedin) && isset($pnuser['nickname']) && isset($individualmenus)) {
                    $individualmenus->statusmenu($pnloggedin, $pnuser['nickname']);
                  }
                  ?></b>
            </td>
            <td width="375" align="right">
                <b class="small">
                  <?PHP echo L_QUICKLINKS; ?>: <a
                            href="index.php?page=news&subpage=add"><?PHP echo L_NEWS_WRITENEWS; ?></a> | <a
                            href="index.php?page=news&subpage=show"><?PHP echo L_NEWS_SHOWNEWS; ?></a> | <a
                            href="index.php?page=users&subpage=search"><?PHP echo L_USR_SEARCHUSR; ?></a>
                </b>
            </td>
        </tr>
    </table>
    <br><br>

    <table border="0" cellpadding="4" cellspacing="1" width="100%" bgcolor="#6078A0">

      <?PHP
      $pnloggedin = $pnloggedin ?? 'NO';
      if ($pnloggedin != "YES") {

        include(__DIR__ . "/login.inc.php");

      } else {

        $allowed_files = [
          "login.inc.php",
          "news.inc.php",
          "other_license.inc.php",
          "users_show.inc.php",
          "news_edit.inc.php",
          "other.inc.php",
          "news_search.inc.php",
          "profile.inc.php",
          "templates_edit.inc.php",
          "templates_show.inc.php",
          "users_search.inc.php",
          "news_show.inc.php",
          "categories_add.inc.php",
          "categories_show.inc.php",
          "permissions.inc.php",
          "permissions_add.inc.php",
          "permissions_edit.inc.php",
          "main.inc.php",
          "templates.inc.php",
          "users_add.inc.php",
          "other_help.inc.php",
          "templates_add.inc.php",
          "users_edit.inc.php",
          "news_add.inc.php",
          "users.inc.php",
          "categories.inc.php",
          "permissions_show.inc.php",
          "categories_edit.inc.php",
          "configuration.inc.php"
        ];

        if (!isset($_GET['page'])) {
          $_GET['page'] = "main";
        }

        $file_to_include = $_GET['page'] . ".inc.php";

        if (in_array($file_to_include, $allowed_files) && file_exists($file_to_include)) {
          include($file_to_include);
        } else {
          ?>
            <tr>
                <td bgcolor="#3F5070" align="center"><b>

                        <b class="headline"><?PHP echo L_TITLE_DOCUMENTNOTFOUND; ?></b>

                    </b></td>
            </tr>

            </td>
            <td bgcolor="#001F3F" valign="top">

                <center><?PHP echo L_ALL_NOPAGE; ?></center>

            </td></tr>
        <?PHP }
      } ?>

    </table>
    </td></tr>
    </table>
    </center>
    <br>
    <center>

      <?PHP
      $endoutput = explode(" ", microtime());
      $startop = (float)$startoutput[1] + (float)$startoutput[0];
      $endop = (float)$endoutput[1] + (float)$endoutput[0];
      $outputtime = round($endop - $startop, 3);
      ?>
        <small><?PHP echo L_ALL_PAGECREATEDIN; ?> <?PHP echo $outputtime; ?> <?PHP echo L_ALL_SECONDSBY; ?> <a
                    href="http://www.powerscripts.org"
                    target="_powerscripts"><?PHP echo $psdesignscript . " " . $pn_config['version']; ?> &copy; 2001-2023
                PowerScripts</a></small>
    </center>

    </body>
    </html>
<?PHP if ($pn_config['acpuffer'] == TRUE) {
  ob_implicit_flush();
} ?>