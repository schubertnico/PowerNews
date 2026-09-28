<?php

declare(strict_types=1);

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

/* This is the extern language file - you can edit all outputs from here */

/* German language file written by PowerScripts (Stefan Kraemer) */

/* Users */
define('L_USR_WRONGEMAIL', 'Die angegebene E-Mail-Adresse scheint nicht korrekt zu sein!');
define('L_USR_USRALREADYEXISTS', 'Es existiert bereits ein Benutzer mit diesem Nickname oder dieser E-Mail-Adresse!');
define('L_USR_REGISTERED', 'Sie haben sich erfolgreich registriert und sollten in einigen Momenten eine E-Mail mit Ihren Benutzerdaten erhalten');
define('L_USR_LOGGEDIN', 'Sie haben sich erfolgreich eingeloggt!');
define('L_USR_WRONGPASSWORD', 'Das angegebene Passwort ist nicht korrekt!');
define('L_USR_NOUSR', 'Es existiert kein Benutzer mit diesem Nickname!');
define('L_USR_NOUSRREGISTERED', 'Es ist kein Benutzer mit diesem Nickname oder dieser E-Mail-Adresse angemeldet!');
define('L_USR_TOOMANYSEARCHRESULTS', 'Es sind zu viele Benutzer gefunden worden. Bitte spezifizieren Sie Ihre Suchangabe!');
define('L_USR_DATASENT', 'Die Daten wurden erfolgreich an Ihre E-Mail-Adresse gesendet!');
define('L_USR_CANTSENDMAIL', 'Die Daten-E-Mail konnte nicht gesendet werden, bitte versuchen Sie es nochmals!');
define('L_USR_NICKNAMEOREMAILALREADYUSED', 'Der gewählte Nickname oder die gewählte E-Mail-Adresse wird bereits von einem anderen Benutzer genutzt!');
define('L_USR_PASSNOTEQUAL', 'Die beiden angegebenen Passwörter stimmen nicht überein!');
define('L_USR_NOTLOGGEDIN', 'Sie sind nicht eingeloggt!');
define('L_USR_CANNOTLOGOUT', 'Sie können sich nicht ausloggen, wenn Sie nicht eingeloggt sind!');
define('L_USR_PROFILEEDITED', 'Ihr Profil wurde erfolgreich editiert. Sollten Sie Ihr Passwort geändert haben, so müssen Sie sich erneut einloggen!');
define('L_USR_ALREADYLOGGEDIN', 'Sie sind bereits eingeloggt.');
define('L_USR_RESETMAIL_BODY', "Hallo {NICKNAME},\n\nfür Ihr Konto bei PowerNews auf {URL} wurde ein neues Passwort angefordert. Über den folgenden Link können Sie innerhalb von {VALIDMINUTES} Minuten ein neues Passwort festlegen:\n\n{RESETLINK}\n\nWenn Sie kein neues Passwort angefordert haben, ignorieren Sie diese E-Mail. Ihr bisheriges Passwort bleibt gültig.\n\nBitte nicht auf diese automatisch generierte E-Mail antworten!");
define('L_USR_RESETTITLE', 'Neues Passwort festlegen');
define('L_USR_RESETINTRO', 'Hallo %s, bitte geben Sie Ihr neues Passwort zweimal ein. Es muss mindestens 8 Zeichen lang sein.');
define('L_USR_NEWPASSWORD', 'Neues Passwort');
define('L_USR_REPEATNEWPASSWORD', 'Neues Passwort wiederholen');
define('L_USR_SAVEPASSWORD', 'Passwort speichern');
define('L_USR_RESETINVALID', 'Der Link zum Festlegen eines neuen Passworts ist ungültig oder abgelaufen. Bitte fordern Sie einen neuen Link an.');
define('L_USR_PASSWORDRESET', 'Ihr neues Passwort ist gespeichert. Sie können sich jetzt damit einloggen.');
define('L_USR_INVALIDREGISTRATION', 'Ungültige Eingabe. Der Nickname muss 3 bis 30 Zeichen lang sein (Buchstaben, Ziffern, Punkt, Unterstrich, Bindestrich), und die E-Mail-Adresse muss gültig sein.');
define('L_USR_REGISTRATIONFAILED', 'Die Registrierung ist fehlgeschlagen. Bitte versuchen Sie es später erneut.');
define('L_USR_TOOMANYATTEMPTS', 'Zu viele Fehlversuche. Bitte versuchen Sie es in 15 Minuten erneut.');
define('L_USR_LOGINFAILED', 'Nickname oder Passwort ist nicht korrekt.');
define('L_USR_DATAREQUESTSENT', 'Falls ein Konto mit diesen Daten existiert, wurde eine E-Mail mit einem Link zum Festlegen eines neuen Passworts versendet. Der Link ist 60 Minuten gültig.');
define('L_USR_TOOMANYREQUESTS', 'Zu viele Anfragen. Bitte versuchen Sie es später erneut.');
define('L_USR_INVALIDPROFILE', 'Ungültige Eingabe. Bitte prüfen Sie Nickname, E-Mail-Adresse und Homepage.');
define('L_USR_PASSWORDTOOSHORT', 'Das Passwort muss mindestens 8 Zeichen lang sein.');

/* News */
define('L_NEWS_NONEWS', 'Keine News vorhanden');
define('L_NEWS_CHOOSENEWS', 'Sie müssen einen gültigen Newseintrag wählen!');
define('L_NEWS_NOCOMMENTS', 'Keine Kommentare vorhanden');
define('L_NEWS_UNKNOWN', 'Unbekannt');
define('L_NEWS_GUEST', 'Gast');
define('L_NEWS_CATSDEACTIVATED', 'Kategorien sind deaktiviert');
define('L_NEWS_WRONGCAT', 'Ungültige Kategorie');
define('L_NEWS_NOHEADLINES', 'Keine Headlines vorhanden');
define('L_NEWS_CANNOTPOSTCOMMENTS', 'Sie können keine Kommentare posten, wenn Sie nicht registriert und eingeloggt sind!');
define('L_NEWS_COMMENTPOSTED', 'Ihr Kommentar wurde erfolgreich gepostet!');
define('L_NEWS_HOURS', 'Stunden');
define('L_NEWS_MINUTES', 'Minuten');
define('L_NEWS_SECONDS', 'Sekunden');
define('L_NEWS_TIMEBETWEEN2COMMENTS', 'Zwischen zwei Kommentaren muss einige Zeit vergangen sein!');
define('L_NEWS_NONEWSFOUND', 'Keine News zum Suchbegriff gefunden!');
define('L_NEWS_NOCATS', 'Keine Kategorien vorhanden!');
define('L_NEWS_NOCATS_ERROR', 'Fehler: Keine Kategorien verfügbar! Bitte wenden Sie sich an den Administrator.');
define('L_NEWS_NOCATS_CANNOT_SEND', 'News können derzeit nicht eingesendet werden, da keine Kategorien verfügbar sind. Bitte versuchen Sie es später erneut.');
define('L_NEWS_SELECTCAT_ERROR', 'Bitte wählen Sie eine Kategorie aus!');
define('L_NEWS_CHOOSECAT', 'Kategorie wählen');
define('L_NEWS_NEWSSENTIN', 'Ihre News wurden erfolgreich eingesendet');
define('L_NEWS_CANNOTSENDNEWS', 'Sie müssen registriert und eingeloggt sein, um News senden zu können!');
define('L_NEWS_NONEWSSENDIN', 'Es können keine News eingesendet werden!');
define('L_NEWS_MORE', 'mehr');
define('L_NEWS_RL_TITLE', 'Titel');
define('L_NEWS_RL_URL', 'URL');
define('L_NEWS_RL_TARGET', 'Ziel');
define('L_NEWS_RELATEDLINKS', 'Weiterführende Links');
define('L_NEWS_COMMENTTOOLONG', 'Der Kommentar ist zu lang (höchstens %d Zeichen).');
define('L_NEWS_NEWSNOTFOUND', 'Die News wurde nicht gefunden.');

/* E-Mail */
define('L_EMAIL_TITLE', 'PowerNews Autobenachrichtigung');
define('L_EMAIL_AUTHOR', 'PowerNews Automailer');

/* Templates */
define('L_TEMPL_CANNOTLOADTEMPL', 'Das Template konnte nicht geladen werden!');
define('L_TEMPL_JANUARY', 'Januar');
define('L_TEMPL_FEBRUARY', 'Februar');
define('L_TEMPL_MARCH', 'März');
define('L_TEMPL_APRIL', 'April');
define('L_TEMPL_MAY', 'Mai');
define('L_TEMPL_JUNE', 'Juni');
define('L_TEMPL_JULY', 'Juli');
define('L_TEMPL_AUGUST', 'August');
define('L_TEMPL_SEPTEMBER', 'September');
define('L_TEMPL_OCTOBER', 'Oktober');
define('L_TEMPL_NOVEMBER', 'November');
define('L_TEMPL_DECEMBER', 'Dezember');

/* Other */
define('L_ALL_FILLALL', 'Sie müssen alle Felder ausfüllen!');
define('L_ALL_CSRFINVALID', 'Das Formular ist abgelaufen oder ungültig. Bitte laden Sie die Seite neu und versuchen Sie es erneut.');
