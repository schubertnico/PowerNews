<?php

declare(strict_types=1);

/* PowerNews is a PHP and mySQL based newsscript - www.powerscripts.org */
/* Copyright (C) 2001-2023 PowerScripts                                 */

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

/* This is a standard admin language file - you can edit all admin outputs from here */

/* German language file written by PowerScripts (Stefan Kraemer) */

/* Categories */
define('L_CAT_TITLE_CATEGORIES', 'K A T E G O R I E N');
define('L_CAT_ADDCAT', 'Kategorie hinzuf&uuml;gen');
define('L_CAT_SHOWCATS', 'Kategorien anzeigen');
define('L_CAT_FILLALL', 'Du musst einen Titel und eine Beschreibung angeben');
define('L_CAT_ANDPIC', 'sowie ein Bild hochladen');
define('L_CAT_CATADDED', 'Die Kategorie wurde erfolgreich hinzugef&uuml;gt!');
define('L_CAT_TITLE', 'Titel');
define('L_CAT_TITLEDESC', "Der Titel unter dem die Kategorie erscheinen soll (Kein ./\:*?&lt;&gt;|\")");
define('L_CAT_DESCRIPTION', 'Beschreibung');
define('L_CAT_DESCRIPTIONDESC', 'Die Beschreibung f&uuml;r die neue Kategorie (max. 255 Zeichen/HTML erlaubt)');
define('L_CAT_PIC', 'Bild');
define('L_CAT_PICDESC', 'Bild f&uuml;r die neue Kategorie (GIF/JPG/JPEG/PNG)');
define('L_CAT_ADDNEWCAT', 'Neue Kategorie hinzuf&uuml;gen');
define('L_CAT_OTHERCATWITHTITLEEXISTS', 'Es existiert bereits eine Kategorie mit diesem Titel!');
define('L_CAT_WRONGCATTITLE', 'Ung&uuml;ltiger Kategorietitel');
define('L_CAT_PICSONLYINTHISFORMAT', 'Bilder d&uuml;rfen nur im GIF/JPG/JPEG/PNG Format hochgeladen werden!');
define('L_CAT_PICUPLOADERROR', 'Das Bild konnte nicht hochgeladen werden!');
define('L_CAT_CATADDERROR', 'Die Kategorie konnte nicht hinzugef&uuml;gt werden!');
define('L_CAT_NOCATSAVAILABLE', 'Keine Kategorien vorhanden!');
define('L_CAT_CATSAREDEACTIVATED', 'Kategorien sind deaktiviert!');
define('L_CAT_EDITED', 'Die Kategorie wurde erfolgreich editiert!');
define('L_CAT_EDITCAT', 'Kategorie editieren');
define('L_CAT_UPLOADPIC', 'Bild hochladen');
define('L_CAT_UPLOADPICDESC', 'Klicke hier wenn ein <b>neues</b> Bild hochgeladen werden soll!');
define('L_CAT_CURRENTPIC', 'Aktuelles Bild');
define('L_CAT_STATUS', 'Status');
define('L_CAT_STATUSDESC', 'Der Status der Kategorie');
define('L_CAT_CHOOSECAT', 'Du musst eine Kategorie ausw&auml;hlen!');
define('L_CAT_NONEXISTINGCAT', 'Die gew&auml;hlte Kategorie existiert nicht!');
define('L_CAT_CANNOTDELETEOLDPIC', 'Das alte Bild konnte nicht gel&ouml;scht werden!');
define('L_CAT_PICRENAMEERROR', 'Das Bild konnte nicht umbenannt werden!');
define('L_CAT_CATEDITERROR', 'Die Kategorie konnte nicht editiert werden!');
define('L_CAT_CLICKFORDETAILS', 'Per Klick auf den Titel einer Kategorie kann diese editiert werden!');

/* Configuration */
define('L_TITLE_CONFIGURATION', 'K O N F I G U R A T I O N');
define('L_CONF_FILLALL', 'Du musst alle Felder ausf&uuml;llen!');
define('L_CONF_EDITED', 'Die Konfiguration wurde erfolgreich editiert!');
define('L_CONF_CATEGORIES', 'Kategorien');
define('L_CONF_CATEGORIES_DESC', 'Sollen Kategorien verwendet werden?');
define('L_CONF_CATPICS', 'Kategorie Bilder');
define('L_CONF_CATPICS_DESC', 'Sollen Bilder f&uuml;r Kategorien verwendet werden?');
define('L_CONF_COMMENTS', 'Kommentare');
define('L_CONF_COMMENTS_DESC', 'Sollen Kommentare f&uuml;r die News verwendet werden?');
define('L_CONF_WRITECOMMENTS', 'Kommentare schreiben');
define('L_CONF_WRITECOMMENTS_DESC', 'Wer darf Kommentare schreiben?');
define('L_CONF_GUESTSANDREGS', 'G&auml;ste & Registrierte');
define('L_CONF_REGS', 'Registrierte');
define('L_CONF_MORETEXT', 'Textaufteilung');
define('L_CONF_MORETEXT_DESC', 'Sollen die News in einen kurzen und einen langen Text aufgeteilt werden?');
define('L_CONF_SENDIN', 'Einsenden Funktion');
define('L_CONF_SENDIN_DESC', 'Soll die <b>News einsenden</b> Funktion aktiviert sein?');
define('L_CONF_SENDNEWS', 'News einsenden');
define('L_CONF_SENDNEWS_DESC', 'Wer darf News einsenden?');
define('L_CONF_SMILIES', 'Smilies');
define('L_CONF_SMILIES_DESC', 'Die Benutzung von grafischen Smilies erlauben?');
define('L_CONF_COMMENTSANDNEWS', 'Kommentare & News');
define('L_CONF_NEWS', 'News');
define('L_CONF_BBCODE', 'BB Code');
define('L_CONF_BBCODE_DESC', 'Die Benutzung von BB Code erlauben?');
define('L_CONF_HTML', 'HTML');
define('L_CONF_HTML_DESC', 'Die Benutzung von HTML erlauben?');
define('L_CONF_DATEFORMAT', 'Datumsformat');
define('L_CONF_DATEFORMAT_DESC', 'Wie soll das Datum formatiert werden? (<a href="./?page=other&subpage=help#configuration.dateformat">Hilfe</a>)');
define('L_CONF_TEMPLATE', 'Template');
define('L_CONF_TEMPLATE_DESC', 'Welches Template soll verwendet werden?');
define('L_CONF_URL', 'URL');
define('L_CONF_URL_DESC', 'Die URL zur Homepage (ohne / am Ende)');
define('L_CONF_EMAIL', 'E-Mail');
define('L_CONF_EMAIL_DESC', 'Die E-Mail Adresse des Webmasters');
define('L_CONF_HEADLINES', 'Headlines');
define('L_CONF_HEADLINES_DESC', 'Die Anzahl der Headlines pro Seite');
define('L_CONF_NEWS_DESC', 'Die Anzahl der Newseintr&auml;ge pro Seite');
define('L_CONF_SPAMPROTECT', 'Spamschutz');
define('L_CONF_SPAMPROTECT_DESC', 'Wie lange ist die Zwangspause zwischen 2 Kommentarposts (in Sekunden)');
define('L_CONF_EDITCONFIG', 'Konfiguration editieren');
define('L_CONF_ONLYNUMBERS', 'Bei Headlines/News pro Seite und Spamschutz d&uuml;rfen nur Zahlen angegeben werden!');
define('L_CONF_WRONGURL', 'Die URL scheint nicht korrekt zu sein!');
define('L_CONF_WRONGEMAIL', 'Die E-Mail Adresse scheint nicht korrekt zu sein!');
define('L_CONF_NOTEMPLATES', 'Keine Templates vorhanden');
define('L_CONF_RELATEDLINKS', 'Related Links');
define('L_CONF_RELATEDLINKS_DESC', 'Sollen bei den News Related Links verwendet werden?');
define('L_CONF_RELATEDLINKS_NUM', 'Anzahl Related Links');
define('L_CONF_RELATEDLINKS_NUM_DESC', 'Die Anzahl von Related Links die pro Newseintrag angegeben werden k&ouml;nnen');
define('L_CONF_TIMEFORMAT', 'Zeitformat');
define('L_CONF_TIMEFORMAT_DESC', 'Wie soll die Zeit formatiert werden? (<a href="./?page=other&subpage=help#configuration.dateformat">Hilfe</a>)');
define('L_CONF_EDITFAILED', 'Die Konfiguration konnte nicht editiert werden!');

/* Menu */
define('L_MENU_TEMPLATES', 'Templates');
define('L_MENU_USERS', 'Users');
define('L_MENU_PERMISSIONS', 'Berechtigungen');
define('L_MENU_CONFIG', 'Konfiguration');
define('L_MENU_CATEGORIES', 'Kategorien');
define('L_MENU_NEWS', 'News');
define('L_MENU_OTHER', 'Sonstiges');
define('L_MENU_EXTERN', 'Externe Seite');
define('L_MENU_ADDTEMPLATE', 'Template hinzuf&uuml;gen');
define('L_MENU_SHOWTEMPLATES', 'Templates anzeigen');
define('L_MENU_ADDUSER', 'Benutzer hinzuf&uuml;gen');
define('L_MENU_SHOWUSER', 'Benutzer anzeigen');
define('L_MENU_SEARCHUSER', 'Benutzer suchen');
define('L_MENU_ADDPERMISSIONS', 'Berechtigungen hinzuf&uuml;gen');
define('L_MENU_SHOWPERMISSIONS', 'Berechtigungen auflisten');
define('L_MENU_EDITCONFIG', 'Konfiguration editieren');
define('L_MENU_ADDCAT', 'Kategorie hinzuf&uuml;gen');
define('L_MENU_SHOWCATS', 'Kategorien anzeigen');
define('L_MENU_CATSDEACTIVATED', 'Kategorien sind deaktiviert');
define('L_MENU_ADDNEWS', 'News schreiben');
define('L_MENU_SHOWNEWS', 'News anzeigen');
define('L_MENU_SEARCHNEWS', 'News suchen');
define('L_MENU_HELP', 'Hilfe');
define('L_MENU_LICENSE', 'Lizenzbedinungen');
define('L_MENU_PSHP', 'PowerScripts Homepage');
define('L_MENU_CHOOSESECTION', 'W&auml;hle eine Sektion');

/* Users */
define('L_USR_NICKNAME', 'Nickname');
define('L_USR_PASSWORD', 'Passwort');
define('L_USR_COOKIESMUSTBEENABLED', '<u>Achtung:</u> F&uuml;r einen erfolgreichen Login m&uuml;ssen Cookies aktiviert sein!');
define('L_USR_LOGINOK', 'Login war erfolgreich');
define('L_USR_WRONGPW', 'Das angegebene Passwort ist nicht korrekt!');
define('L_USR_NOUSR', 'Der angegebene Benutzer existiert nicht in der Datenbank!');
define('L_USR_NICKANDPW', 'Du musst Nickname und Passwort angeben');
define('L_USR_NICKANDEMAIL', 'Du musst Nickname und E-Mail Adresse angeben!');
define('L_USR_USRADDED', 'Benutzer wurde erfolgreich hinzugef&uuml;gt!');
define('L_USR_ADDUSR', 'Benutzer hinzuf&uuml;gen');
define('L_USR_NICKNAME_DESC', 'Der Nickname f&uuml;r den neuen Benutzer');
define('L_USR_EMAIL', 'E-Mail');
define('L_USR_EMAIL_DESC', 'Die E-Mail Adresse f&uuml;r den neuen Benutzer');
define('L_USR_SHOWEMAIL', 'E-Mail anzeigen');
define('L_USR_SHOWEMAIL_DESC', 'Soll die E-Mail Adresse des Benutzers angezeigt werden?');
define('L_USR_SENDMAIL', 'E-Mail senden');
define('L_USR_SENDMAIL_DESC', 'Soll dem Benutzer eine E-Mail mit seinen Daten gesendet werden?');
define('L_USR_USREDITED', 'Der Benutzer wurde erfolgreich editiert!');
define('L_USR_EDITUSR', 'Benutzer editieren');
define('L_USR_CHOOSEUSER', 'Du musst einen Benutzer ausw&auml;hlen!');
define('L_USR_NEWPW', 'Neues Passwort');
define('L_USR_NEWPW_DESC', 'Soll f&uuml;r den Benutzer ein neues Passwort generiert werden?');
define('L_USR_STATUS', 'Status');
define('L_USR_STATUS_DESC', 'Der Status des Benutzers');
define('L_USR_ADMIN', 'Admin');
define('L_USR_SHOWUSR', 'Benutzer anzeigen');
define('L_USR_SHOWUSR_DESC', 'Klicke auf einen Nickname um den Benutzer zu editieren! Es werden 25 Benutzer pro Seite angezeigt.');
define('L_USR_SEARCHSTRINGNEEDED', 'Du musst einen Suchbegriff angeben');
define('L_USR_SEARCHUSR', 'Benutzer suchen');
define('L_USR_SEARCHFIELD', 'Suchfeld');
define('L_USR_SEARCHFIELD_DESC', 'W&auml;hle aus in welchem Feld gesucht werden soll');
define('L_USR_USRID', 'Benutzer-ID');
define('L_USR_SEARCHSTRING', 'Suchbegriff');
define('L_USR_SEARCHSTRING_DESC', 'Gib an nach was Du suchen willst');
define('L_USR_PROFILEEDITED', 'Dein Benutzerprofil wurde editiert!');
define('L_USR_NICKNAME_DESC_PROF', 'Eigener Nickname');
define('L_USR_EMAIL_DESC_PROF', 'Eigene korrekte E-Mail Adresse');
define('L_USR_SHOWEMAIL_DESC_PROF', 'Soll die eigene E-Mail Adresse angezeigt werden?');
define('L_USR_PASSWORD_DESC_PROF', 'Eigenes Passwort (mit Best&auml;tigung)');
define('L_USR_PROFILE_DESC', 'Nach &auml;nderung des Passworts ist ein erneuter Login notwendig.');
define('L_USR_EDITPROFILE', 'Profil editieren');
define('L_USR_NOADMIN', 'Der angegebene Benutzer hat keinen Zugriff auf die Administrations-Ebene!');
define('L_USR_PLEASELOGIN', 'Bitte einloggen');
define('L_USR_LOGIN', 'Login');
define('L_USR_LOGOUT', 'Ausloggen');
define('L_USR_WRONGEMAIL', 'Die angegebene E-Mail Adresse ist ung&uuml;ltig!');
define('L_USR_USRALREADYEXISTS', 'Es existiert bereits ein Benutzer mit diesem Nickname oder dieser E-Mail Adresse!');
define('L_USR_NOUSRINDB', 'Keine Benutzer in der Datenbank');
define('L_USR_INSERTNICKNAMEANDEMAIL', 'Du musst Nickname und E-Mail angeben!');
define('L_USR_NOUSRFOUND', 'Es wurde kein Benutzer gefunden!');
define('L_USR_PWNOTCONFIRMED', 'Das Passwort wurde nicht korrekt best&auml;tigt!');
define('L_USR_HELLO', 'Hallo');

/* Templates */
define('L_TEMPL_ADDTEMPLATE', 'Template hinzuf&uuml;gen');
define('L_TEMPL_SHOWTEMPLATES', 'Templates anzeigen');
define('L_TEMPL_TITLE', 'Titel');
define('L_TEMPL_TITLE_DESC', 'Der Titel des Templates');
define('L_TEMPL_EDITTEMPLATE', 'Template editieren');
define('L_TEMPL_GENERAL', 'A L L G E M E I N E S');
define('L_TEMPL_OUTPUT', 'A U S G A B E');
define('L_TEMPL_INPUT', 'F O R M U L A R E / E I N G A B E N');
define('L_TEMPL_EMAILS', 'E M A I L S');
define('L_TEMPL_DELETE', 'L&ouml;schen');
define('L_TEMPL_DELETE_DESC', 'Soll das Template gel&ouml;scht werden?');
define('L_TEMPL_MESSAGE', 'Nachricht');
define('L_TEMPL_MESSAGE_DESC', 'Ausgabe der Standardnachricht (z.B. Fehler)');
define('L_TEMPL_HEADLINES', 'Headlines');
define('L_TEMPL_HEADLINES_DESC', 'Ausgabe der Headlines');
define('L_TEMPL_NEWS', 'News');
define('L_TEMPL_NEWS_DESC', 'Ausgabe der News');
define('L_TEMPL_COMMENTS', 'Kommentare');
define('L_TEMPL_COMMENTS_DESC', 'Ausgabe der Kommentare');
define('L_TEMPL_USERMENU', 'Benutzermen&uuml;');
define('L_TEMPL_USERMENU1_DESC', 'Ausgabe des Benutermen&uuml;s (nicht eingeloggt)');
define('L_TEMPL_USERMENU2_DESC', 'Ausgabe des Benutzermen&uuml;s (eingeloggt)');
define('L_TEMPL_COMMENTFORM', 'Kommentarformular');
define('L_TEMPL_COMMENTFORM_DESC', 'Formular f&uuml;r die Eingabe von Kommentaren');
define('L_TEMPL_FORMTARGET', 'Formularziel');
define('L_TEMPL_REGISTERFORM', 'Registrierungsformular');
define('L_TEMPL_REGISTERFORM_DESC', 'Formular f&uuml;r die Registrierung von Benutzer');
define('L_TEMPL_LOGINFORM', 'Loginformular');
define('L_TEMPL_LOGINFORM_DESC', 'Formular f&uuml;r die Eingabe des Benutzerlogins');
define('L_TEMPL_LOGOUTFORM', 'Logoutbest&auml;tigung');
define('L_TEMPL_LOGOUTFORM_DESC', 'Best&auml;tigung f&uuml;r den Logout');
define('L_TEMPL_LINKTARGET', 'Linkziel:');
define('L_TEMPL_SENDDATAFORM', 'Datensendenformular');
define('L_TEMPL_SENDDATAFORM_DESC', 'Formular f&uuml;r das Datensenden');
define('L_TEMPL_PROFILEFORM', 'Profilformular');
define('L_TEMPL_PROFILEFORM_DESC', 'Formular f&uuml;r die Eingabe des Benutzerprofils');
define('L_TEMPL_ARCHIVEFORM', 'Archivformular');
define('L_TEMPL_ARCHIVEFORM_DESC', '');
define('L_TEMPL_SENDNEWSFORM', 'Newseinsendenformular');
define('L_TEMPL_SENDNEWSFORM_DESC', 'Formular f&uuml;r das Einsenden von News');
define('L_TEMPL_USERADDEDMAIL', 'Benutzer hinzugef&uuml;gt - E-Mail');
define('L_TEMPL_USERADDEDMAIL_DESC', 'E-Mail wenn ein Benutzer von einem Admin hinzugef&uuml;gt wurde');
define('L_TEMPL_USEREDITEDMAIL', 'Benutzer editiert - E-Mail');
define('L_TEMPL_USEREDITEDMAIL_DESC', 'E-Mail wenn ein Benutzer von einem Admin editiert wurde');
define('L_TEMPL_USERREGISTEREDMAIL', 'Benutzer registriert - E-Mail');
define('L_TEMPL_USERREGISTEREDMAIL_DESC', 'E-Mail wenn sich ein Benutzer registriert hat');
define('L_TEMPL_DATAMAIL', 'Benutzerdaten - E-Mail');
define('L_TEMPL_DATAMAIL_DESC', 'E-Mail sich ein Benutzer seine Daten zuschicken l&auml;sst');
define('L_TEMPL_CHOOSETEMPLATE', 'Du musst ein Template w&auml;hlen');
define('L_TEMPL_SHOW_DESC', 'Per Klick auf ein Template kann dieses editiert werden.');
define('L_TEMPL_TITLENEEDED', 'Du musst einen Titel f&uuml;r das neue Template angeben!');
define('L_TEMPL_TEMPLATEADDED', 'Das Template wurde erfolgreich hinzugef&uuml;gt!');
define('L_TEMPL_TEMPLATEALREADYEXISTS', 'Es existiert bereits ein Template mit diesem Titel');
define('L_TEMPL_NOSTANDARDTEMPLATE', 'Das Standardtemplate existiert nicht!');
define('L_TEMPL_NOTEMPLATES', 'Keine Templates vorhanden');
define('L_TEMPL_INSERTALL', 'Du musst alle Felder ausf&uuml;llen!');
define('L_TEMPL_NOSTANDARDEDIT', 'Das Standardtemplate darf nicht editiert/gel&ouml;scht werden!');
define('L_TEMPL_TEMPLATEEDITED', 'Das Template wurde erfolgreich editiert!');
define('L_TEMPL_RIGHTTEMPLATENEEDED', 'Es muss ein g&uuml;ltiges Template gew&auml;hlt werden!');
define('L_TEMPL_TEMPLATEDELETED', 'Das Template wurde erfolgreich gel&ouml;scht!');
define('L_TEMPL_RELATEDLINKS', 'Related Links');
define('L_TEMPL_RELATEDLINKS_DESC', 'Die ausgabe f&uuml;r einen einzigen Related Link');

/* Permissions */
define('L_PERM_ADDPERMISSIONS', 'Berechtigungen hinzuf&uuml;gen');
define('L_PERM_SHOWPERMISSIONS', 'Berechtigungen anzeigen');
define('L_PERM_INSERTNICK', 'Du musst einen Nickname angeben!');
define('L_PERM_PERMISSIONADDED', 'Berechtigungen wurden erfolgreich hinzugef&uuml;gt!');
define('L_PERM_NICK', 'Nickname');
define('L_PERM_NICK_DESC', 'Der korrekte Nickname des Benutzers');
define('L_PERM_PERMISSIONS', 'Berechtigungen');
define('L_PERM_PERMISSIONS_DESC', 'Welche Berechtigungen soll der Benutzer besitzen?');
define('L_PERM_READ', 'Lesen');
define('L_PERM_WRITE', 'Schreiben');
define('L_PERM_TEMPLATES', 'Templates');
define('L_PERM_CONFIG', 'Konfiguration');
define('L_PERM_USER', 'Benutzer');
define('L_PERM_CATS', 'Kategorien');
define('L_PERM_NEWS', 'News');
define('L_PERM_COMMENTS', 'Kommentare');
define('L_PERM_CHOOSEADMIN', 'Du musst einen Admin w&auml;hlen!');
define('L_PERM_PERMISSIONSDELETED', 'Die Berechtigungen wurden erfolgreich gel&ouml;scht!');
define('L_PERM_PERMISSIONSEDITED', 'Die Berechtigungen wurden erfolgreich editiert!');
define('L_PERM_EDITPERMISSIONS', 'Berechtigungen editieren');
define('L_PERM_DELETE', 'L&ouml;schen');
define('L_PERM_DELETE_DESC', 'Sollen die Berechtigungen des Benutzers gel&ouml;scht werden?');
define('L_PERM_SHOW_DESC', 'Klicke auf einen Nickname um die Berechtiungen zu editieren.');
define('L_PERM_CANTWRITETODB', 'Die Berechtigungen konnten nicht in die Datenbank eingetragen werden!');
define('L_PERM_ALREADYADMIN', 'Der angegebene Benutzer ist bereits Admin!');
define('L_PERM_USERNOTEXISTING', 'Es existiert kein Benutzer mit dem angegebenen Nickname oder der Benutzer ist deaktivert!');
define('L_PERM_NOPERMISSIONS', 'Keine Berechtigungen vorhanden');
define('L_PERM_SECTION', 'Sektion');
define('L_PERM_PERMISSIONSNOTDELETED', 'Die Berechtigungen konnten nicht gel&ouml;scht werden!');
define('L_PERM_NOADMIN', 'Der gew&auml;hlte Benutzer ist kein Admin!');
define('L_PERM_CANNOTWRITETODB', '');

/* News */
define('L_NEWS_WRITENEWS', 'News schreiben');
define('L_NEWS_SHOWNEWS', 'News anzeigen');
define('L_NEWS_EDITNEWS', 'News editieren');
define('L_NEWS_SEARCHNEWS', 'News suchen');
define('L_NEWS_TITLEANDTEXTNEEDED', 'Es muss ein Newstitel und ein Newstext angegeben werden');
define('L_NEWS_ALSOCATEGORY', 'sowie eine Kategorie gew&auml;hlt werden');
define('L_NEWS_NEWSADDED', 'News wurden erfolgreich geschrieben!');
define('L_NEWS_CATEGORY', 'Kategorie');
define('L_NEWS_CATEGORY_DESC', 'Die Kategorie unter der der Newseintrag erscheinen soll');
define('L_NEWS_TITLE', 'Der Titel des Newseintrags');
define('L_NEWS_TITLE_DESC', 'Der Titel des Newseintrags');
define('L_NEWS_TEXT', 'Text');
define('L_NEWS_TEXT_DESC', 'Der Text des Newseintrags');
define('L_NEWS_ON', 'an');
define('L_NEWS_OFF', 'aus');
define('L_NEWS_LONGTEXT', 'Langer Text');
define('L_NEWS_LONGTEXT_DESC', 'Der lange Text des Newseintrags');
define('L_NEWS_COMMENTSEDITED', 'Die Kommentare wurden erfolgreich editiert!');
define('L_NEWS_NEWSDELETED', 'Der Newseintrag wurde erfolgreich gel&ouml;scht!');
define('L_NEWS_NEWSEDITED', 'Der Newseintrag wurde erfolgreich editiert!');
define('L_NEWS_DELETE', 'L&ouml;schen');
define('L_NEWS_DELETE_DESC', 'Soll der Newseintrag sowie alle eventuell vorhandenen Kommentare gel&ouml;scht werden?');
define('L_NEWS_STATUS', 'Status');
define('L_NEWS_STATUS_DESC', 'Der Status des Newseintrags');
define('L_NEWS_EDITCOMMENTS', 'Kommentare editieren');
define('L_NEWS_CHOOSENEWS', 'Du musst einen g&uuml;ltigen Newseintrag ausw&auml;hlen!');
define('L_NEWS_DATE', 'Datum');
define('L_NEWS_SHOW_DESC', 'Klicke auf einen Newstitel um die News zu editieren.');
define('L_NEWS_SEARCHFIELD', 'Suchfeld');
define('L_NEWS_SEARCHFIELD_DESC', 'In welchem Feld soll gesucht werden?');
define('L_NEWS_NEWSID', 'News-ID');
define('L_NEWS_SEARCHSTRING', 'Suchbegriff');
define('L_NEWS_SEARCHSTRING_DESC', 'Nach was soll gesucht werden?');
define('L_NEWS_SEARCHSTRINGNEEDED', 'Es muss ein Suchbegriff angegeben werden!');
define('L_NEWS_CHOOSECAT', 'Kategorie w&auml;hlen');
define('L_NEWS_NOCATSAVAILABLE', 'Noch keine Kategorien vorhanden!');
define('L_NEWS_ADDINGFAILED', 'Die News konnten nicht hinzugef&uuml;gt werden!');
define('L_NEWS_BADCAT', 'Ung&uuml;ltige Kategorie');
define('L_NEWS_NONEWS', 'Keine News in der Datenbank');
define('L_NEWS_NOCOMMENTS', 'Keine Kommentare in der Datenbank');
define('L_NEWS_DELETECOMMENT', 'L&ouml;schen');
define('L_NEWS_DELETECOMMENT_DESC', 'Soll der Kommentar gel&ouml;scht werden?');
define('L_NEWS_INFO', 'Info');
define('L_NEWS_INFO_DESC', 'Einige Informationen');
define('L_NEWS_WRITTENBY', 'Geschrieben von');
define('L_NEWS_GUEST', 'Gast');
define('L_NEWS_ONDATE', 'am');
define('L_NEWS_AT', 'um');
define('L_NEWS_COMMENTEXT_DESC', 'Der Kommentartext');
define('L_NEWS_ONECOMMENTWRONG', 'Einer der Kommentare ist nicht g&uuml;ltig!');
define('L_NEWS_COMMENTEDITERROR', 'Beim Editieren der Kommentare ist ein Fehler aufgetreten!');
define('L_NEWS_NEWSNOTDELETED', 'Der Newseintrag konnte nicht gel&ouml;scht werden!');
define('L_NEWS_NEWSNOTEDITED', 'Der Newseintrag konnte nicht editiert werden!');
define('L_NEWS_RELATEDLINKS', 'Related Links');
define('L_NEWS_RELATEDLINKS_DESC', 'Hier kannst Du Related Links zu diesem Newseintrag posten');
define('L_NEWS_RL_TITLE', 'Titel');
define('L_NEWS_RL_URL', 'URL/Pfad');
define('L_NEWS_RL_TARGET', 'Ziel');
define('L_NEWS_TIME', 'Erscheinungstermin');
define('L_NEWS_TIME_DESC', 'Wann sollen die News auf der Startseite erscheinen?');
define('L_NEWS_DAY', 'Tag');
define('L_NEWS_MONTH', 'Monat');
define('L_NEWS_YEAR', 'Jahr');
define('L_NEWS_HOUR', 'Stunde');
define('L_NEWS_MIN', 'Minute');
define('L_NEWS_JANUARY', 'Januar');
define('L_NEWS_FEBRUARY', 'Februar');
define('L_NEWS_MARCH', 'M&auml;rz');
define('L_NEWS_APRIL', 'April');
define('L_NEWS_MAY', 'Mai');
define('L_NEWS_JUNE', 'Juni');
define('L_NEWS_JULY', 'Juli');
define('L_NEWS_AUGUST', 'August');
define('L_NEWS_SEPTEMBER', 'September');
define('L_NEWS_OCTOBER', 'Oktober');
define('L_NEWS_NOVEMBER', 'November');
define('L_NEWS_DECEMBER', 'Dezember');
define('L_NEWS_NOCOMMENTTEXT', 'Bei einem oder mehreren Kommentaren fehlt der Text!');

/* Other */
define('L_OTHER_HELP', 'Hilfe');
define('L_OTHER_LICENSE', 'Lizenzbedinungen');
define('L_OTHER_LICENSE_DESC', 'PowerNews wird unter den Regelungen der <b>General Public License</b> vertrieben. Die aktuellste Version der Lizenz kann auf der <a href="http://www.powerscripts.org" target="_blank">PowerScripts Homepage</a> nachgelesen werden.');
define('L_OTHER_NOLOCALLICENSE', 'Der Zugriff auf die lokale Kopie der Lizenz ist fehlgeschlagen. Eventuell wurde die Datei gel&ouml;scht.');

/* Titles */
define('L_TITLE_LOGIN', 'L O G I N');
define('L_TITLE_MAIN', 'W I L L K O M M E N');
define('L_TITLE_NEWS', 'N E W S');
define('L_TITLE_PERMISSIONS', 'B E R E C H T I G U N G E N');
define('L_TITLE_TEMPLATES', 'T E M P L A T E S');
define('L_TITLE_OTHER', 'S O N S T I G E S');
define('L_TITLE_PROFILE', 'P R O F I L');
define('L_TITLE_USERS', 'B E N U T Z E R');
define('L_TITLE_DOCUMENTNOTFOUND', '4 0 4&nbsp;&nbsp;-&nbsp;&nbsp;D O K U M E N T&nbsp;&nbsp;N I C H T&nbsp;&nbsp;G E F U N D E N');

/* E-Mails */
define('L_EMAIL_SUBJECT', 'PowerNews Auto-Benachrichtigung');
define('L_EMAIL_AUTHOR', 'PowerScripts Automailer');

/* Something else */
define('L_ALL_NOPAGE', 'Die aufgerufene Seite existiert nicht!');
define('L_ALL_SUBPAGENOTFOUND', 'Die aufgerufene Unterseite wurde nicht gefunden!');
define('L_ALL_CHOOSESUBPAGE', 'Bitte w&auml;hlen Sie eine Unterseite');
define('L_ALL_ACTIVATED', 'Aktiviert');
define('L_ALL_DEACTIVATED', 'Deaktiviert');
define('L_ALL_UNCHECKED', 'Ungepr&uuml;ft');
define('L_ALL_ACCESSDENIED', 'Zugriff verweigert!');
define('L_ALL_RESETDATA', 'Daten zur&uuml;cksetzten');
define('L_ALL_YES', 'Ja');
define('L_ALL_NO', 'Nein');
define('L_ALL_NOPAGES', 'Keine Seiten');
define('L_QUICKLINKS', 'Quicklinks');
define('L_ALL_PAGECREATEDIN', 'Seite erstellt in');
define('L_ALL_SECONDSBY', 'Sekunden von');
define('L_ALL_WELCOME', 'Willkommen im PowerNews Admincenter<br><br>Bitte senden Sie entdeckte Bugs an <a href="mailto:bugs@powerscripts.org?subject=PowerNews Bug">bugs@powerscripts.org</a>!');
