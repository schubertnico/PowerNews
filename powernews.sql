# ====================================================================
#  PowerNews – Datenbankschema und Grunddaten
# ====================================================================
#  Diese Datei spielt der Web-Installer (install.php) ein. Installation
#  per Kommandozeile: mysql -u BENUTZER -p DATENBANK < powernews.sql
#  und danach einen Administrator anlegen (siehe INSTALLATION.md).
#
#  - Kein DROP TABLE: Auf eine Datenbank mit PowerNews-Tabellen
#    angewendet, bricht der Import ab, statt Daten zu löschen.
#  - Kein Standard-Administrator: Das Konto legt der Installer mit dem
#    selbst gewählten Nickname und Passwort an.
#  - Seiten-URL und Absenderadresse in pn_config sind neutrale
#    Platzhalter (example.com); der Installer trägt die echten Werte ein.
#  - Erlaubt sind nur SET NAMES, CREATE TABLE und INSERT INTO für
#    pn_-Tabellen – alles andere weist der Installer ab.
#  - Stand: PowerNews 3.12. Bestehende Installationen bringt update.php
#    auf diesen Stand (fehlende Tabellen, Migrationen in pn_migrations).
# ====================================================================

SET NAMES utf8mb4;

# --------------------------------------------------------
# Tabelle `pn_categories` (Kategorien, mit Startkategorie „Allgemein“)
# --------------------------------------------------------

CREATE TABLE `pn_categories` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `description` tinytext NOT NULL,
  `picture` varchar(250) NOT NULL default '',
  `status` enum('Activated','Deactivated') NOT NULL default 'Activated',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `pn_categories` (`id`, `name`, `description`, `picture`, `status`) VALUES (1, 'Allgemein', 'Standardkategorie', '', 'Activated');

# --------------------------------------------------------
# Tabelle `pn_comments` (Kommentare)
# --------------------------------------------------------

CREATE TABLE `pn_comments` (
  `id` int(11) NOT NULL auto_increment,
  `newsid` int(10) NOT NULL default '0',
  `userid` int(11) NOT NULL default '0',
  `time` int(14) NOT NULL default '0',
  `text` text NOT NULL,
  `ip` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

# --------------------------------------------------------
# Tabelle `pn_config` (genau eine Zeile mit den Einstellungen)
# --------------------------------------------------------

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

# Sichere Vorgaben: Kommentare und Einsendungen nur für registrierte
# Benutzer. url und email setzt der Installer (Schritt „Website“).
INSERT INTO `pn_config` VALUES ('YES', 'NO', 'YES', 'Registered', 'NO', 'YES', 'Registered', 'Comments', 'Comments', 'Comments', '%d.%m.%Y', '%H:%M', 1, 'https://www.example.com', 'noreply@example.com', 10, 10, 30, 'NO', 5);

# --------------------------------------------------------
# Tabelle `pn_news` (News)
# --------------------------------------------------------

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

# --------------------------------------------------------
# Tabelle `pn_permissions` (Rechte im Adminbereich; die Zeile des ersten Administrators legt der Installer an)
# --------------------------------------------------------

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

# --------------------------------------------------------
# Tabelle `pn_sessions` (Anmeldungen in Frontend und Adminbereich)
# --------------------------------------------------------

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
# Tabelle `pn_login_attempts` (Fehlversuchsbremse beim Login)
# --------------------------------------------------------

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
# Tabelle `pn_password_resets` („Passwort vergessen“: Einmal-Links, 60 Minuten
# gültig; gespeichert wird nur der SHA-256-Hash des Tokens)
# --------------------------------------------------------

CREATE TABLE `pn_password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `created` int(14) NOT NULL,
  `expires` int(14) NOT NULL,
  `ip` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `idx_token` (`token_hash`),
  KEY `idx_userid` (`userid`),
  KEY `idx_ip_created` (`ip`, `created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

# --------------------------------------------------------
# Tabelle `pn_templates` (Vorlagen, mit dem Standard-Template „Default“ 3.12:
# Meldungstypen, {CATPIC}, „Weiterführende Links“, „Passwort vergessen“ mit
# Einmal-Link; identisch mit pninc/default_template.inc.php)
# --------------------------------------------------------

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `pn_templates` VALUES (1,'Default','<div class=\"alert alert-{TYPE} border\" role=\"alert\"><h2 class=\"h6 fw-bold mb-2\">{HEADING}</h2><p class=\"mb-2\">{MESSAGE}</p><a href=\"{LINK}\" class=\"btn btn-sm btn-outline-dark\">{LINKTEXT}</a></div>','<div class=\"d-flex flex-wrap align-items-baseline mb-1\"><small class=\"text-muted me-2\">{DATE} @ {TIME}</small><a href=\"news.php?newsid={ID}\" class=\"text-decoration-none\">{TITLE}</a></div>','<a id=\"news-{ID}\"></a><article class=\"card pn-news-card mb-4\"><h2 class=\"card-header h6 mb-0\">{TITLE}</h2><div class=\"card-body\"><div class=\"row g-3\"><div class=\"col-12\">{CATPIC}<div class=\"news-text mb-3\">{TEXT}</div><div class=\"text-center\">{MORE}</div><!--RELATEDLINKS_START--><div class=\"border rounded p-2 bg-light mt-3\"><strong class=\"d-block mb-2\">Weiterführende Links</strong><ul class=\"list-unstyled small mb-0\">{RELATEDLINKS}</ul></div><!--RELATEDLINKS_END--></div></div></div><footer class=\"card-footer text-end small text-muted\">geschrieben von {AUTHOR} am {DATE} um {TIME} &middot; <span class=\"badge text-bg-secondary\">{CATEGORY}</span><!--COMMENTS_START--> &middot; <a href=\"news.php?newsid={ID}&showcomments=YES\">Kommentare ({COMMENTS})</a><!--COMMENTS_END--></footer></article>','<div class=\"card mb-3\"><div class=\"card-header bg-light\"><strong>{AUTHOR}</strong> <span class=\"text-muted small\">&middot; {DATE} um {TIME}</span></div><div class=\"card-body\">{TEXT}</div></div>','<ul class=\"list-unstyled small mb-0\"><li>&raquo; <a href=\"./user.php\">Registrieren</a></li><li>&raquo; <a href=\"./user.php?page=login\">Login</a></li><li>&raquo; <a href=\"./user.php?page=senddata\">Passwort vergessen</a></li></ul>','<ul class=\"list-unstyled small mb-0\"><li>&raquo; <a href=\"./user.php?page=profile\">Profil</a></li><li>&raquo; <a href=\"./user.php?page=logout\">Logout</a></li></ul>','<li>&raquo;&nbsp;<a href=\"{URL}\" target=\"{TARGET}\" rel=\"noopener noreferrer\">{TITLE}</a></li>','<form accept-charset=\"UTF-8\" action=\"comments.php?newsid={NEWSID}\" method=\"post\" class=\"card mb-4\"><h2 class=\"card-header h6 mb-0\">Kommentar schreiben</h2><div class=\"card-body\"><div class=\"mb-3\"><span class=\"form-label fw-bold d-block\">Name</span><div class=\"form-control-plaintext\">{NAME}</div></div><div class=\"mb-3\"><label for=\"pn_text\" class=\"form-label fw-bold\">Text</label><textarea class=\"form-control\" name=\"text\" id=\"pn_text\" rows=\"5\" required aria-describedby=\"pn_text_help\"></textarea><div id=\"pn_text_help\" class=\"form-text\">Enter für neue Zeile, kein HTML.</div></div><button type=\"submit\" class=\"btn btn-primary\">Kommentar posten</button><input type=\"hidden\" name=\"csrf_token\" value=\"{CSRF}\"></div></form>','<form accept-charset=\"UTF-8\" action=\"user.php?pndata[send]=YES\" method=\"post\" class=\"card mb-4\"><h2 class=\"card-header h6 mb-0\">Registrieren</h2><div class=\"card-body\"><div class=\"mb-3\"><label for=\"pn_nickname\" class=\"form-label fw-bold\">Nickname</label><input class=\"form-control\" name=\"pndata[nickname]\" id=\"pn_nickname\" maxlength=\"30\" autocomplete=\"username\" required aria-describedby=\"pn_nickname_help\"><div id=\"pn_nickname_help\" class=\"form-text\">3 bis 30 Zeichen: Buchstaben, Ziffern, Punkt, Unterstrich, Bindestrich.</div></div><div class=\"mb-3\"><label for=\"pn_email\" class=\"form-label fw-bold\">E-Mail-Adresse</label><input type=\"email\" class=\"form-control\" name=\"pndata[email]\" id=\"pn_email\" maxlength=\"100\" autocomplete=\"email\" required></div><div class=\"row g-2 mb-3\"><div class=\"col-12 col-md-6\"><label for=\"pn_password\" class=\"form-label fw-bold\">Passwort</label><input type=\"password\" class=\"form-control\" name=\"pndata[password]\" id=\"pn_password\" minlength=\"8\" maxlength=\"72\" autocomplete=\"new-password\" required aria-describedby=\"pn_password_help\"></div><div class=\"col-12 col-md-6\"><label for=\"pn_password2\" class=\"form-label fw-bold\">Passwort wiederholen</label><input type=\"password\" class=\"form-control\" name=\"pndata[password2]\" id=\"pn_password2\" minlength=\"8\" maxlength=\"72\" autocomplete=\"new-password\" required></div><div id=\"pn_password_help\" class=\"form-text\">Mindestens 8 Zeichen. Nach der Registrierung ist die Anmeldung sofort mit Nickname und Passwort möglich.</div></div><div class=\"form-check mb-3\"><input class=\"form-check-input\" type=\"checkbox\" name=\"pndata[showemail]\" value=\"YES\" id=\"pn_showemail\"><label class=\"form-check-label\" for=\"pn_showemail\">E-Mail-Adresse im Profil anzeigen</label></div><button type=\"submit\" class=\"btn btn-primary\">Registrieren</button><input type=\"hidden\" name=\"csrf_token\" value=\"{CSRF}\"></div></form>','<form accept-charset=\"UTF-8\" action=\"user.php?page=login&pndata[login]=YES\" method=\"post\" class=\"card mb-4\"><h2 class=\"card-header h6 mb-0\">Login</h2><div class=\"card-body\"><div class=\"mb-3\"><label for=\"pn_nickname\" class=\"form-label fw-bold\">Nickname</label><input class=\"form-control\" name=\"pndata[nickname]\" id=\"pn_nickname\" maxlength=\"50\" autocomplete=\"username\" required></div><div class=\"mb-3\"><label for=\"pn_password\" class=\"form-label fw-bold\">Passwort</label><input type=\"password\" class=\"form-control\" name=\"pndata[password]\" id=\"pn_password\" maxlength=\"128\" autocomplete=\"current-password\" required></div><button type=\"submit\" class=\"btn btn-primary\">Einloggen</button> <a class=\"ms-2\" href=\"./user.php?page=senddata\">Passwort vergessen?</a><input type=\"hidden\" name=\"csrf_token\" value=\"{CSRF}\"></div></form>','<div class=\"card mb-4\"><h2 class=\"card-header h6 mb-0\">Ausloggen</h2><div class=\"card-body text-center\"><p class=\"mb-3\">Bist Du sicher, dass Du Dich ausloggen willst, <strong>{NICKNAME}</strong>?</p><form method=\"POST\" action=\"user.php?page=logout\" class=\"d-inline\"><input type=\"hidden\" name=\"csrf_token\" value=\"{CSRF}\"><button type=\"submit\" class=\"btn btn-danger me-2\">Ja, ausloggen</button></form><a class=\"btn btn-outline-secondary\" href=\"index.php\">Nein, abbrechen</a></div></div>','<form accept-charset=\"UTF-8\" action=\"user.php?page=senddata\" method=\"post\" class=\"card mb-4\"><h2 class=\"card-header h6 mb-0\">Passwort vergessen</h2><div class=\"card-body\"><div class=\"mb-3\"><label for=\"pn_searchstring\" class=\"form-label fw-bold\">Nickname oder E-Mail-Adresse</label><input class=\"form-control\" name=\"pndata[searchstring]\" id=\"pn_searchstring\" maxlength=\"100\" required aria-describedby=\"pn_search_help\"><div id=\"pn_search_help\" class=\"form-text\">Du erhältst eine E-Mail mit einem Link, über den Du ein neues Passwort festlegst. Der Link ist 60 Minuten gültig, bis dahin bleibt Dein bisheriges Passwort bestehen.</div></div><button type=\"submit\" class=\"btn btn-primary\">Link anfordern</button><input type=\"hidden\" name=\"csrf_token\" value=\"{CSRF}\"></div></form>','<form accept-charset=\"UTF-8\" action=\"user.php?page=profile&pndata[send]=YES\" method=\"post\" class=\"card mb-4\"><h2 class=\"card-header h6 mb-0\">Profil editieren</h2><div class=\"card-body\"><div class=\"mb-3\"><label for=\"pn_nickname\" class=\"form-label fw-bold\">Nickname</label><input class=\"form-control\" name=\"pndata[nickname]\" id=\"pn_nickname\" maxlength=\"30\" value=\"{NICKNAME}\" required></div><div class=\"mb-3\"><label for=\"pn_email\" class=\"form-label fw-bold\">E-Mail-Adresse</label><input type=\"email\" class=\"form-control\" name=\"pndata[email]\" id=\"pn_email\" maxlength=\"100\" value=\"{EMAIL}\" required></div><div class=\"form-check mb-3\"><input class=\"form-check-input\" type=\"checkbox\" name=\"pndata[showemail]\" value=\"YES\" id=\"pn_showemail\" {SHOWEMAIL}><label class=\"form-check-label\" for=\"pn_showemail\">E-Mail-Adresse im Profil anzeigen</label></div><div class=\"row g-2 mb-3\"><div class=\"col-12 col-md-6\"><label for=\"pn_password\" class=\"form-label fw-bold\">Neues Passwort</label><input type=\"password\" class=\"form-control\" name=\"pndata[password]\" id=\"pn_password\" value=\"{PASSWORD}\" maxlength=\"128\" autocomplete=\"new-password\"></div><div class=\"col-12 col-md-6\"><label for=\"pn_password2\" class=\"form-label fw-bold\">Passwort wiederholen</label><input type=\"password\" class=\"form-control\" name=\"pndata[password2]\" id=\"pn_password2\" value=\"{PASSWORD}\" maxlength=\"128\" autocomplete=\"new-password\"></div></div><div class=\"row g-2 mb-3\"><div class=\"col-12 col-md-6\"><label for=\"pn_realname\" class=\"form-label fw-bold\">Name</label><input class=\"form-control\" name=\"pndata[realname]\" id=\"pn_realname\" maxlength=\"100\" value=\"{REALNAME}\" autocomplete=\"name\"></div><div class=\"col-12 col-md-6\"><label for=\"pn_city\" class=\"form-label fw-bold\">Wohnort</label><input class=\"form-control\" name=\"pndata[city]\" id=\"pn_city\" maxlength=\"100\" value=\"{CITY}\"></div></div><div class=\"row g-2 mb-3\"><div class=\"col-12 col-md-3\"><label for=\"pn_age\" class=\"form-label fw-bold\">Alter</label><input class=\"form-control\" name=\"pndata[age]\" id=\"pn_age\" maxlength=\"3\" value=\"{AGE}\" inputmode=\"numeric\"></div><div class=\"col-12 col-md-9\"><label for=\"pn_homepage\" class=\"form-label fw-bold\">Homepage</label><input class=\"form-control\" type=\"url\" name=\"pndata[homepage]\" id=\"pn_homepage\" maxlength=\"250\" value=\"{HOMEPAGE}\" placeholder=\"https://\"></div></div><div class=\"d-flex gap-2\"><button type=\"submit\" class=\"btn btn-primary\">Profil speichern</button><button type=\"reset\" class=\"btn btn-outline-secondary\">Zurücksetzen</button></div><input type=\"hidden\" name=\"csrf_token\" value=\"{CSRF}\"></div></form>','<section class=\"card mb-4\"><h2 class=\"card-header h6 mb-0\">Newsarchiv</h2><div class=\"card-body\"><form accept-charset=\"UTF-8\" action=\"archive.php\" method=\"post\" class=\"row g-2 align-items-end mb-3\"><div class=\"col-12 col-md-4\"><label for=\"pn_showyear\" class=\"form-label small text-muted mb-1\">Jahr</label>{SELECTYEAR}</div><div class=\"col-12 col-md-4\"><label for=\"pn_showmonth\" class=\"form-label small text-muted mb-1\">Monat</label>{SELECTMONTH}</div><div class=\"col-12 col-md-4\"><button type=\"submit\" class=\"btn btn-primary w-100\">Anzeigen</button></div></form><div class=\"text-center small text-muted my-2\">oder</div><form accept-charset=\"UTF-8\" action=\"archive.php?pndata[type]=search\" method=\"post\" class=\"row g-2 align-items-end\"><div class=\"col-12 col-md-9\"><label for=\"pn_searchstring\" class=\"form-label small text-muted mb-1\">Suchen nach</label><input class=\"form-control\" name=\"pndata[searchstring]\" id=\"pn_searchstring\" maxlength=\"50\" value=\"{SEARCHSTRING}\"></div><div class=\"col-12 col-md-3\"><button type=\"submit\" class=\"btn btn-outline-primary w-100\">Suchen</button></div></form></div></section>','<form accept-charset=\"UTF-8\" action=\"sendnews.php?pndata[send]=YES\" method=\"post\" class=\"card mb-4\"><h2 class=\"card-header h6 mb-0\">News einsenden</h2><div class=\"card-body\"><div class=\"mb-3\"><span class=\"form-label fw-bold d-block\">Nickname</span><div class=\"form-control-plaintext\">{USER}</div></div><div class=\"mb-3\"><label for=\"pn_catid\" class=\"form-label fw-bold\">Kategorie</label><div>{CATEGORYSELECT}</div></div><div class=\"mb-3\"><label for=\"pn_title\" class=\"form-label fw-bold\">Titel</label><input class=\"form-control\" name=\"pndata[title]\" id=\"pn_title\" maxlength=\"150\" required></div><div class=\"mb-3\"><label for=\"pn_text\" class=\"form-label fw-bold\">Text</label><textarea class=\"form-control\" name=\"pndata[text]\" id=\"pn_text\" rows=\"6\" required></textarea></div><div class=\"mb-3\"><label for=\"pn_moretext\" class=\"form-label fw-bold\">Langer Text</label><textarea class=\"form-control\" name=\"pndata[moretext]\" id=\"pn_moretext\" rows=\"6\" aria-describedby=\"pn_moretext_help\"></textarea><div id=\"pn_moretext_help\" class=\"form-text\">Optionaler ausführlicher Text, der auf der Detailseite erscheint.</div></div>{RELATEDLINKS}<button type=\"submit\" class=\"btn btn-primary\">News einsenden</button><input type=\"hidden\" name=\"csrf_token\" value=\"{CSRF}\"></div></form>','Hallo {NICKNAME},\n\nauf {SITE} wurde ein Zugang für die E-Mail-Adresse {EMAIL} eingerichtet. Das Passwort lässt sich über diesen Link selbst festlegen:\n\n{INVITELINK}\n\nDer Link ist {VALIDHOURS} Stunden gültig und nur einmal verwendbar. Danach ist die Anmeldung mit dem Nickname „{NICKNAME}“ und dem neuen Passwort möglich:\n\n{LOGINLINK}\n\nIst der Link abgelaufen, lässt sich über „Passwort vergessen“ ein neuer anfordern.\n\nBitte nicht auf diese automatisch generierte E-Mail antworten!','Hallo {NICKNAME},\n\ndie Kontodaten auf {SITE} wurden soeben von einem Administrator geändert. Der aktuelle Stand:\n\nNickname: {NICKNAME}\nE-Mail-Adresse: {EMAIL}\n\nDas Passwort bleibt unverändert.\n\nBitte nicht auf diese automatisch generierte E-Mail antworten!','Willkommen bei {SITE}, {NICKNAME}!\n\nDie Registrierung ist abgeschlossen. Die Anmeldung ist ab sofort mit dem Nickname „{NICKNAME}“ und dem bei der Registrierung gewählten Passwort möglich:\n\n{LOGINLINK}\n\nHinterlegte E-Mail-Adresse: {EMAIL}\n\nBitte nicht auf diese automatisch generierte E-Mail antworten!','Hallo {NICKNAME},\n\nfür das Konto „{NICKNAME}“ auf {SITE} wurde ein neues Passwort angefordert. Das neue Passwort lässt sich innerhalb von {VALIDMINUTES} Minuten über diesen Link festlegen:\n\n{RESETLINK}\n\nWurde kein neues Passwort angefordert, diese E-Mail einfach ignorieren. Das bisherige Passwort bleibt gültig.\n\nBitte nicht auf diese automatisch generierte E-Mail antworten!');

# --------------------------------------------------------
# Tabelle `pn_users` (Benutzer; den ersten Administrator legt der Installer an)
# --------------------------------------------------------

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

# --------------------------------------------------------
# Tabelle `pn_migrations` (ausgeführte Datenbank-Migrationen, siehe
# pninc/migrations.inc.php). Eine Neuinstallation ist bereits auf dem Stand
# 3.12 – die Einträge unten markieren die Migrationen als erledigt. Beim Update
# einer älteren Installation legt update.php die Tabelle ohne diese Einträge an
# und führt die Migrationen aus.
# --------------------------------------------------------

CREATE TABLE `pn_migrations` (
  `name` varchar(100) NOT NULL,
  `applied_at` int(14) NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `pn_migrations` (`name`, `applied_at`) VALUES ('3.12-unslash-content', 0), ('3.12-purge-legacy-admin-sessions', 0), ('3.12-password-resets', 0), ('3.12-relatedlinks-json', 0), ('3.12-default-template', 0);
