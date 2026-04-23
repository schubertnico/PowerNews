#
# Tabellenstruktur f�r Tabelle `pn_categories`
#

DROP TABLE IF EXISTS `pn_categories`;
CREATE TABLE `pn_categories` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `description` tinytext NOT NULL,
  `picture` varchar(250) NOT NULL default '',
  `status` enum('Activated','Deactivated') NOT NULL default 'Activated',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

#
# Daten f�r Tabelle `pn_categories`
#


# --------------------------------------------------------

#
# Tabellenstruktur f�r Tabelle `pn_comments`
#

DROP TABLE IF EXISTS `pn_comments`;
CREATE TABLE `pn_comments` (
  `id` int(11) NOT NULL auto_increment,
  `newsid` int(10) NOT NULL default '0',
  `userid` int(11) NOT NULL default '0',
  `time` int(14) NOT NULL default '0',
  `text` text NOT NULL,
  `ip` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

#
# Daten f�r Tabelle `pn_comments`
#


# --------------------------------------------------------

#
# Tabellenstruktur f�r Tabelle `pn_config`
#

DROP TABLE IF EXISTS `pn_config`;
CREATE TABLE `pn_config` (
  `categories` enum('YES','NO') NOT NULL default 'YES',
  `categorypics` enum('YES','NO') NOT NULL default 'YES',
  `comments` enum('YES','NO') NOT NULL default 'YES',
  `commentwriting` enum('Guests/Registered','Registered') NOT NULL default 'Guests/Registered',
  `moretext` enum('YES','NO') NOT NULL default 'YES',
  `sendnews` enum('YES','NO') NOT NULL default 'YES',
  `newssending` enum('Guests/Registered','Registered') NOT NULL default 'Guests/Registered',
  `smilies` enum('NO','Comments','Comments/News','News') NOT NULL default 'NO',
  `bbcode` enum('NO','Comments','Comments/News','News') NOT NULL default 'NO',
  `html` enum('NO','Comments','Comments/News','News') NOT NULL default 'NO',
  `dateformat` varchar(50) NOT NULL default '%d.%m.%Y',
  `timeformat` varchar(50) NOT NULL default '%H:%M',
  `template` int(11) NOT NULL default '0',
  `url` varchar(250) NOT NULL default '',
  `email` varchar(250) NOT NULL default '',
  `headlines` tinyint(2) NOT NULL default '0',
  `news` tinyint(2) NOT NULL default '0',
  `spamprotection` int(10) NOT NULL default '0',
  `relatedlinks` enum('YES','NO') NOT NULL default 'NO',
  `relatedlinks_num` int(2) NOT NULL default '5'
) ENGINE=MyISAM;

#
# Daten f�r Tabelle `pn_config`
#

INSERT INTO `pn_config` VALUES ('YES', 'NO', 'YES', 'Guests/Registered', 'NO', 'YES', 'Guests/Registered', 'Comments', 'Comments/News', 'News', '%d.%m.%Y', '%H:%M', 1, 'http://www.powerscripts.org', 'daemon@powerscripts.org', 10, 10, 30, 'NO', 5);

# --------------------------------------------------------

#
# Tabellenstruktur f�r Tabelle `pn_news`
#

DROP TABLE IF EXISTS `pn_news`;
CREATE TABLE `pn_news` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL default '0',
  `time` int(14) NOT NULL default '0',
  `catid` int(11) NOT NULL default '0',
  `title` varchar(150) NOT NULL default '',
  `text` text NOT NULL,
  `moretext` text NOT NULL,
  `status` enum('Activated','Unchecked','Deactivated') NOT NULL default 'Activated',
  `relatedlinks` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

#
# Daten f�r Tabelle `pn_news`
#


# --------------------------------------------------------

#
# Tabellenstruktur f�r Tabelle `pn_permissions`
#

DROP TABLE IF EXISTS `pn_permissions`;
CREATE TABLE `pn_permissions` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL default '0',
  `canreadtemplates` enum('YES','NO') NOT NULL default 'NO',
  `canwritetemplates` enum('YES','NO') NOT NULL default 'NO',
  `canreadconfig` enum('YES','NO') NOT NULL default 'NO',
  `canwriteconfig` enum('YES','NO') NOT NULL default 'NO',
  `canreadusers` enum('YES','NO') NOT NULL default 'NO',
  `canwriteusers` enum('YES','NO') NOT NULL default 'NO',
  `canreadpermissions` enum('YES','NO') NOT NULL default 'NO',
  `canwritepermissions` enum('YES','NO') NOT NULL default 'NO',
  `canreadcategories` enum('YES','NO') NOT NULL default 'NO',
  `canwritecategories` enum('YES','NO') NOT NULL default 'NO',
  `canreadnews` enum('YES','NO') NOT NULL default 'NO',
  `canwritenews` enum('YES','NO') NOT NULL default 'NO',
  `canreadcomments` enum('YES','NO') NOT NULL default 'NO',
  `canwritecomments` enum('YES','NO') NOT NULL default 'NO',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 ;

#
# Daten f�r Tabelle `pn_permissions`
#

INSERT INTO `pn_permissions` VALUES (1, 1, 'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES');

# --------------------------------------------------------

#
# Tabellenstruktur fuer Tabelle `pn_sessions`
#

DROP TABLE IF EXISTS `pn_sessions`;
CREATE TABLE `pn_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `created` int(14) NOT NULL,
  `expires` int(14) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `ip` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_userid` (`userid`),
  KEY `idx_token` (`token_hash`),
  KEY `idx_expires` (`expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

# --------------------------------------------------------

#
# Tabellenstruktur fuer Tabelle `pn_login_attempts`
#

DROP TABLE IF EXISTS `pn_login_attempts`;
CREATE TABLE `pn_login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(64) NOT NULL,
  `nickname` varchar(100) NOT NULL,
  `success` enum('YES','NO') NOT NULL DEFAULT 'NO',
  `attempted_at` int(14) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ip_time` (`ip`, `attempted_at`),
  KEY `idx_nick_time` (`nickname`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

# --------------------------------------------------------

#
# Tabellenstruktur f�r Tabelle `pn_templates`
#

DROP TABLE IF EXISTS `pn_templates`;
CREATE TABLE `pn_templates` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(100) NOT NULL default '',
  `message` text NOT NULL,
  `headline` text NOT NULL,
  `news` text NOT NULL,
  `comment` text NOT NULL,
  `usermenu` text NOT NULL,
  `usermenu2` text NOT NULL,
  `relatedlinks` text NOT NULL,
  `commentform` text NOT NULL,
  `registerform` text NOT NULL,
  `loginform` text NOT NULL,
  `logout` text NOT NULL,
  `senddataform` text NOT NULL,
  `profileform` text NOT NULL,
  `archive` text NOT NULL,
  `sendnewsform` text NOT NULL,
  `addemail` text NOT NULL,
  `editemail` text NOT NULL,
  `registeremail` text NOT NULL,
  `dataemail` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 ;

#
# Daten f�r Tabelle `pn_templates`
#

INSERT INTO `pn_templates` VALUES (1, 'Default', '<table border="0" cellpadding="3" cellspacing="0" width="100%">\r\n<tr><td bgcolor="#8C8E8C">\r\n<b>Nachricht</b>\r\n</td></tr>\r\n<tr><td bgcolor="#DEDFDE"><br>\r\n<p align="center"><a href="{LINK}">{MESSAGE}</a></p>\r\n</td></tr>\r\n</table><br>', '<small>{DATE} @ {TIME}</small> <a href="#{ID}">{TITLE}</a><br>', '<a name="#{ID}"></a>\r\n<table border="0" cellpadding="3" cellspacing="0" width="100%">\r\n<tr><td bgcolor="#8C8E8C" colspan="2">\r\n<b>{TITLE}</b>\r\n</td></tr>\r\n<tr><td bgcolor="#DEDFDE" valign="top" width="80%">\r\n{TEXT}\r\n</td><td bgcolor="#8C8E8C" valign="top" width="20%">\r\n<b>Related Links</b><br>\r\n<ul>\r\n{RELATEDLINKS}\r\n</ul><br>\r\n<center>{MORE}</center>\r\n</td></tr>\r\n<tr><td colspan="2" align="right" bgcolor="#DEDFDE">\r\ngeschrieben von {AUTHOR} am {DATE} um {TIME} - {CATEGORY} - <a href="news.php?newsid={ID}&showcomments=YES">Kommentare ({COMMENTS})</a>\r\n</td></tr>\r\n</table><br>', '<table border="0" cellpadding="3" cellspacing="0" width="100%">\r\n<tr><td bgcolor="#8C8E8C">\r\n<b>geschrieben von {AUTHOR} am {DATE} um {TIME}</b>\r\n</td></tr>\r\n<tr><td bgcolor="#DEDFDE">\r\n{TEXT}\r\n</td></tr>\r\n</table><br>', '&raquo; <a href="./user.php">Registrieren</a><br>\r\n&raquo; <a href="./user.php?page=login">Login</a><br>\r\n&raquo; <a href="./user.php?page=senddata">Daten senden</a><br>', '&raquo; <a href="./user.php?page=profile">Profil</a><br>\r\n&raquo; <a href="./user.php?page=logout">Logout</a><br>', '&raquo;&nbsp;<a href="{URL}" target="{TARGET}">{TITLE}</a><br>\n', '<form action="comments.php?newsid={NEWSID}" method="post">\r\n<table border="0" cellpadding="3" cellspacing="0" width="100%">\r\n<tr><td bgcolor="#8C8E8C">\r\n<b>Kommentar schreiben</b>\r\n</td></tr>\r\n<tr><td bgcolor="#DEDFDE">\r\n\r\n  <table border="0" cellpadding="3" cellspacing="3">\r\n  <tr><td>\r\n  <b>Name</b>\r\n  </td><td>\r\n  {NAME}\r\n  </td></tr>\r\n  <tr><td valign="top">\r\n  <b>Text</b>\r\n  </td><td>\r\n  <textarea name="text" cols="50" rows="5"></textarea><br>\r\n  <small>Enter f&uuml;r neue Zeile, kein HTML</small>\r\n  </td></tr>\r\n  <tr><td colspan="2" align="center">\r\n  <input type="submit" value="Kommentar posten">\r\n  </td></tr>\r\n  </table>\r\n\r\n</td></tr>\r\n</table>\r\n</form>', '<form action="user.php?pndata[send]=YES" method="post">\r\n<table border="0" cellpadding="3" cellspacing="0" width="100%">\r\n<tr><td bgcolor="#8C8E8C">\r\n<b>Registrieren</b>\r\n</td></tr>\r\n<tr><td bgcolor="#DEDFDE">\r\n\r\n  <table border="0" cellpadding="3" cellspacing="3">\r\n  <tr><td>\r\n  <b>Nickname</b>\r\n  </td><td>\r\n  <input name="pndata[nickname]" size="25" maxlength="50">\r\n  </td></tr>\r\n  <tr><td>\r\n  <b>E-Mail</b>\r\n  </td><td>\r\n  <input name="pndata[email]" size="25" maxlength="100">\r\n  </td></tr>\r\n  <tr><td>\r\n  <b>E-Mail anzeigen</b>\r\n  </td><td>\r\n  <input type="checkbox" name="pndata[showemail]" value="YES">\r\n  </td></tr>\r\n  <tr><td colspan="2">\r\n  <input type="submit" value="Registrieren">\r\n  </td></tr>\r\n  </table>\r\n\r\n</td></tr>\r\n</table>\r\n</form>', '<form action="user.php?page=login&pndata[login]=YES" method="post">\r\n<table border="0" cellpadding="3" cellspacing="0" width="100%">\r\n<tr><td bgcolor="#8C8E8C">\r\n<b>Login</b>\r\n</td></tr>\r\n<tr><td bgcolor="#DEDFDE">\r\n\r\n  <table border="0" cellpadding="3" cellspacing="3">\r\n  <tr><td>\r\n  <b>Nickname</b>\r\n  </td><td>\r\n  <input name="pndata[nickname]" size="25" maxlength="50">\r\n  </td></tr>\r\n  <tr><td>\r\n  <b>Passwort</b>\r\n  </td><td>\r\n  <input name="pndata[password]" size="25" maxlength="25" type="password">\r\n  </td></tr>\r\n  <tr><td colspan="2">\r\n  <input type="submit" value="Einloggen">\r\n  </td></tr>\r\n  </table>\r\n\r\n</td></tr>\r\n</table>\r\n</form>', '<table border="0" cellpadding="3" cellspacing="0" width="100%">\r\n<tr><td bgcolor="#8C8E8C">\r\n<b>Ausloggen</b>\r\n</td></tr>\r\n<tr><td bgcolor="#DEDFDE">\r\n\r\n  <p align="center">Bist Du sicher dass Du Dich ausloggen willst <b>{NICKNAME}</b>?</p>\r\n  <p align="center">[ <a href="user.php?page=logout&pndata[logout]=YES">Ja, ausloggen</a> |\r\n  <a href="index.php">Nein, abbrechen</a> ]</p>\r\n\r\n</td></tr>\r\n</table>', '<form action="user.php?page=senddata" method="post">\r\n<table border="0" cellpadding="3" cellspacing="0" width="100%">\r\n<tr><td bgcolor="#8C8E8C">\r\n<b>Daten senden</b>\r\n</td></tr>\r\n<tr><td bgcolor="#DEDFDE">\r\n\r\n  <table border="0" cellpadding="3" cellspacing="3">\r\n  <tr><td>\r\n  <b>Nickname/E-Mail</b>\r\n  </td><td>\r\n  <input name="pndata[searchstring]" size="25" maxlength="50">\r\n  </td></tr>\r\n  <tr><td colspan="2">\r\n  <input type="submit" value="Daten senden">\r\n  </td></tr>\r\n  </table>\r\n\r\n</td></tr>\r\n</table>\r\n</form>', '<form action="user.php?page=profile&pndata[send]=YES" method="post">\r\n<table border="0" cellpadding="3" cellspacing="0" width="100%">\r\n<tr><td bgcolor="#8C8E8C">\r\n  <b>Profil editieren</b>\r\n</td></tr>\r\n<tr><td bgcolor="#DEDFDE">\r\n\r\n  <table border="0" cellpadding="3" cellspacing="3">\r\n  <tr><td>\r\n    <b>Nickname</b>\r\n  </td><td>\r\n    <input name="pndata[nickname]" size="25" maxlength="50" value="{NICKNAME}">\r\n  </td></tr>\r\n  <tr><td>\r\n    <b>E-Mail</b>\r\n  </td><td>\r\n    <input name="pndata[email]" size="25" maxlength="100" value="{EMAIL}">\r\n  </td></tr>\r\n  <tr><td>\r\n    <b>E-Mail anzeigen</b>\r\n  </td><td>\r\n    <input type="checkbox" name="pndata[showemail]" value="YES" {SHOWEMAIL}>\r\n  </td></tr>\r\n  <tr><td>\r\n    <b>Passwort</b>\r\n  </td><td>\r\n    <input type="password" name="pndata[password]" value="{PASSWORD}">\r\n    <input type="password" name="pndata[password2]" value="{PASSWORD}">\r\n  </td></tr>\r\n  <tr><td>\r\n    <b>Realname</b>\r\n  </td><td>\r\n    <input name="pndata[realname]" value="{REALNAME}" size="25" maxlength="100">\r\n  </td></tr>\r\n  <tr><td>\r\n    <b>Wohnort</b>\r\n  </td><td>\r\n    <input name="pndata[city]" value="{CITY}" size="25" maxlength="100">\r\n  </td></tr>\r\n  <tr><td>\r\n    <b>Alter</b>\r\n  </td><td>\r\n    <input name="pndata[age]" value="{AGE}" size="3" maxlength="2">\r\n  </td></tr>\r\n  <tr><td>\r\n    <b>Homepage</b>\r\n  </td><td>\r\n    <input name="pndata[homepage]" value="{HOMEPAGE}" size="25" maxlength="250">\r\n  </td></tr>\r\n  <tr><td>\r\n    <b>ICQ</b>\r\n  </td><td>\r\n    <input name="pndata[icq]" value="{ICQ}" size="11" maxlength="10">\r\n  </td></tr>\r\n  <tr><td colspan="2">\r\n    <input type="submit" value="Profil editieren"> <input type="reset" value="Profil zur&uuml;cksetzten">\r\n  </td></tr>\r\n  </table>\r\n\r\n</td></tr>\r\n</table>\r\n</form>', '<table border="0" cellpadding="3" cellspacing="0" width="100%">\r\n<tr><td bgcolor="#8C8E8C">\r\n<b>Newsarchiv</b>\r\n</td></tr>\r\n<tr><td bgcolor="#DEDFDE">\r\n\r\n  <form action="archive.php" method="post">\r\n  News aus dem Jahr {SELECTYEAR} und dem Monat {SELECTMONTH} anzeigen <input type="submit" value="Anzeigen">\r\n  </form>\r\n  <b>oder</b><br>\r\n  <br>\r\n  <form action="archive.php?pndata[type]=search" method="post">\r\n  Suchen nach <input name="pndata[searchstring]" size="25" maxlength="50" value="{SEARCHSTRING}"> <input type="submit" value="Suchen">\r\n  </form>\r\n\r\n</td></tr>\r\n</table><br>', '<form action="sendnews.php?pndata[send]=YES" method="post">\r\n<table border="0" cellpadding="3" cellspacing="0" width="100%">\r\n<tr><td bgcolor="#8C8E8C">\r\n<b>News einsenden</b>\r\n</td></tr>\r\n<tr><td bgcolor="#DEDFDE">\r\n\r\n  <table border="0" cellpadding="3" cellspacing="3">\r\n  <tr><td>\r\n  <b>Nickname</b>\r\n  </td><td>\r\n  {USER}\r\n  </td></tr>\r\n  <tr><td>\r\n  <b>Kategorie</b>\r\n  </td><td>\r\n  {CATEGORYSELECT}\r\n  </td></tr>\r\n  <tr><td>\r\n  <b>Titel</b>\r\n  </td><td>\r\n  <input name="pndata[title]" size="25" maxlength="50">\r\n  </td></tr>\r\n  <tr><td valign="top">\r\n  <b>Text</b>\r\n  </td><td>\r\n  <textarea name="pndata[text]" cols="50" rows="10"></textarea>\r\n  </td></tr>\r\n  <tr><td valign="top">\r\n  <b>Langer Text</b>\r\n  </td><td>\r\n  <textarea name="pndata[moretext]" cols="50" rows="10"></textarea>\r\n  </td></tr>\r\n  <tr><td colspan="2">\r\n  <input type="submit" value="News einsenden">\r\n  </td></tr>\r\n  </table>\r\n\r\n</td></tr>\r\n</table>\r\n</form>', 'Hallo {NICKNAME},\r\n\r\ndu wurdest soeben von einem Administrator bei PowerNews auf {URL} registriert. Deine Daten lauten wie folgt:\r\n\r\nNickname: {NICKNAME}\r\nE-Mail: {EMAIL}\r\nPasswort: {PASSWORD}\r\n\r\nBitte nicht auf diese automatisch generierte E-Mail antworten!', 'Hallo {NICKNAME},\r\n\r\nsoeben wurde dein PowerNews Profil auf {URL} von einem Administrator editiert. Deine neuen Daten lauten wie folgt:\r\n\r\nNickname: {NICKNAME}\r\neMail: {EMAIL}\r\nPasswort: {PASSWORD}\r\n\r\nBitte nicht auf diese automatisch generierte eMail antworten!', 'Hallo {NICKNAME},\r\n\r\ndu hast Dich soeben bei PowerNews auf {URL} registriert. Deine Daten lauten wie folgt:\r\n\r\nNickname: {NICKNAME}\r\neMail: {EMAIL}\r\nPasswort: {PASSWORD}\r\n\r\nBitte nicht auf diese automatisch generierte eMail antworten!', 'Hallo {NICKNAME},\r\n\r\nDu hast dir soeben von PowerNews auf {URL} Deine Daten zuschicken lassen. Solltest Du Deine Daten nicht abgefragt haben, so ignoriere diese eMail!\r\n\r\nNickname: {NICKNAME}\r\neMail: {EMAIL}\r\nPasswort: {PASSWORD}\r\n\r\nBitte nicht auf diese automatisch generierte eMail antworten!');

# --------------------------------------------------------

#
# Tabellenstruktur f�r Tabelle `pn_users`
#

DROP TABLE IF EXISTS `pn_users`;
CREATE TABLE `pn_users` (
  `id` int(11) NOT NULL auto_increment,
  `nickname` varchar(100) NOT NULL default '',
  `email` varchar(250) NOT NULL default '',
  `password` varchar(100) NOT NULL default '',
  `registered` int(14) NOT NULL default '0',
  `showemail` enum('YES','NO') NOT NULL default 'YES',
  `formathelp` enum('YES','NO') NOT NULL default 'NO',
  `status` enum('Activated','Deactivated') NOT NULL default 'Activated',
  `realname` varchar(100) NOT NULL default '',
  `city` varchar(100) NOT NULL default '',
  `age` int(2) NOT NULL default '0',
  `homepage` varchar(250) NOT NULL default '',
  `icq` int(10) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 ;

#
# Daten f�r Tabelle `pn_users`
#

INSERT INTO `pn_users` VALUES (1, 'powernews', 'daemon@powerscripts.org', 'cG93ZXJuZXdz', 1077979922, 'NO', 'NO', 'Activated', '', '', 0, '', 0);
