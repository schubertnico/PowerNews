<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

/*
 * Inhalt des Default-Templates (ID 1) ab Version 3.12.
 *
 * Die Migration „3.12-default-template“ ersetzt damit alle Template-Felder, die noch
 * unverändert dem Auslieferungsstand 3.11 entsprechen. Angepasste Felder bleiben unberührt.
 * powernews.sql enthält dieselben Inhalte (geprüft in DefaultTemplateTest).
 */

/**
 * Felder des Default-Templates 3.12 (Spaltenname => Inhalt).
 *
 * @return array<string, string>
 */
function pn_default_template(): array
{
    return [
        // B29: Meldungstyp als Farbe, Überschrift je Typ, Rücklink als eigene Schaltfläche
        'message' => '<div class="alert alert-{TYPE} border" role="alert"><h2 class="h6 fw-bold mb-2">{HEADING}</h2><p class="mb-2">{MESSAGE}</p><a href="{LINK}" class="btn btn-sm btn-outline-dark">{LINKTEXT}</a></div>',
        'headline' => '<div class="d-flex flex-wrap align-items-baseline mb-1"><small class="text-muted me-2">{DATE} @ {TIME}</small><a href="news.php?newsid={ID}" class="text-decoration-none">{TITLE}</a></div>',
        // B48: {CATPIC}, deutsche Überschrift, Kommentarlink nur bei aktivierten Kommentaren
        'news' => '<a id="news-{ID}"></a><article class="card pn-news-card mb-4"><h2 class="card-header h6 mb-0">{TITLE}</h2><div class="card-body"><div class="row g-3"><div class="col-12">{CATPIC}<div class="news-text mb-3">{TEXT}</div><div class="text-center">{MORE}</div><!--RELATEDLINKS_START--><div class="border rounded p-2 bg-light mt-3"><strong class="d-block mb-2">Weiterführende Links</strong><ul class="list-unstyled small mb-0">{RELATEDLINKS}</ul></div><!--RELATEDLINKS_END--></div></div></div><footer class="card-footer text-end small text-muted">geschrieben von {AUTHOR} am {DATE} um {TIME} &middot; <span class="badge text-bg-secondary">{CATEGORY}</span><!--COMMENTS_START--> &middot; <a href="news.php?newsid={ID}&showcomments=YES">Kommentare ({COMMENTS})</a><!--COMMENTS_END--></footer></article>',
        'comment' => '<div class="card mb-3"><div class="card-header bg-light"><strong>{AUTHOR}</strong> <span class="text-muted small">&middot; {DATE} um {TIME}</span></div><div class="card-body">{TEXT}</div></div>',
        // B44: „Passwort vergessen“ statt „Daten senden“
        'usermenu' => '<ul class="list-unstyled small mb-0"><li>&raquo; <a href="./user.php">Registrieren</a></li><li>&raquo; <a href="./user.php?page=login">Login</a></li><li>&raquo; <a href="./user.php?page=senddata">Passwort vergessen</a></li></ul>',
        'usermenu2' => '<ul class="list-unstyled small mb-0"><li>&raquo; <a href="./user.php?page=profile">Profil</a></li><li>&raquo; <a href="./user.php?page=logout">Logout</a></li></ul>',
        'relatedlinks' => '<li>&raquo;&nbsp;<a href="{URL}" target="{TARGET}" rel="noopener noreferrer">{TITLE}</a></li>',
        'commentform' => '<form accept-charset="UTF-8" action="comments.php?newsid={NEWSID}" method="post" class="card mb-4"><h2 class="card-header h6 mb-0">Kommentar schreiben</h2><div class="card-body"><div class="mb-3"><span class="form-label fw-bold d-block">Name</span><div class="form-control-plaintext">{NAME}</div></div><div class="mb-3"><label for="pn_text" class="form-label fw-bold">Text</label><textarea class="form-control" name="text" id="pn_text" rows="5" required aria-describedby="pn_text_help"></textarea><div id="pn_text_help" class="form-text">Enter für neue Zeile, kein HTML.</div></div><button type="submit" class="btn btn-primary">Kommentar posten</button><input type="hidden" name="csrf_token" value="{CSRF}"></div></form>',
        // Registrierung mit selbst gewähltem Passwort (zweimal)
        'registerform' => '<form accept-charset="UTF-8" action="user.php?pndata[send]=YES" method="post" class="card mb-4"><h2 class="card-header h6 mb-0">Registrieren</h2><div class="card-body"><div class="mb-3"><label for="pn_nickname" class="form-label fw-bold">Nickname</label><input class="form-control" name="pndata[nickname]" id="pn_nickname" maxlength="30" autocomplete="username" required aria-describedby="pn_nickname_help"><div id="pn_nickname_help" class="form-text">3 bis 30 Zeichen: Buchstaben, Ziffern, Punkt, Unterstrich, Bindestrich.</div></div><div class="mb-3"><label for="pn_email" class="form-label fw-bold">E-Mail-Adresse</label><input type="email" class="form-control" name="pndata[email]" id="pn_email" maxlength="100" autocomplete="email" required></div><div class="row g-2 mb-3"><div class="col-12 col-md-6"><label for="pn_password" class="form-label fw-bold">Passwort</label><input type="password" class="form-control" name="pndata[password]" id="pn_password" minlength="8" maxlength="72" autocomplete="new-password" required aria-describedby="pn_password_help"></div><div class="col-12 col-md-6"><label for="pn_password2" class="form-label fw-bold">Passwort wiederholen</label><input type="password" class="form-control" name="pndata[password2]" id="pn_password2" minlength="8" maxlength="72" autocomplete="new-password" required></div><div id="pn_password_help" class="form-text">Mindestens 8 Zeichen. Nach der Registrierung ist die Anmeldung sofort mit Nickname und Passwort möglich.</div></div><div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="pndata[showemail]" value="YES" id="pn_showemail" aria-describedby="pn_showemail_help"><label class="form-check-label" for="pn_showemail">Namen mit E-Mail-Adresse verlinken (öffentlich sichtbar)</label><div id="pn_showemail_help" class="form-text">Der Name unter News und Kommentaren wird dann zum Mail-Link, die Adresse ist für alle Besucher sichtbar.</div></div><button type="submit" class="btn btn-primary">Registrieren</button><input type="hidden" name="csrf_token" value="{CSRF}"></div></form>',
        'loginform' => '<form accept-charset="UTF-8" action="user.php?page=login&pndata[login]=YES" method="post" class="card mb-4"><h2 class="card-header h6 mb-0">Login</h2><div class="card-body"><div class="mb-3"><label for="pn_nickname" class="form-label fw-bold">Nickname</label><input class="form-control" name="pndata[nickname]" id="pn_nickname" maxlength="50" autocomplete="username" required></div><div class="mb-3"><label for="pn_password" class="form-label fw-bold">Passwort</label><input type="password" class="form-control" name="pndata[password]" id="pn_password" maxlength="128" autocomplete="current-password" required></div><button type="submit" class="btn btn-primary">Einloggen</button> <a class="ms-2" href="./user.php?page=senddata">Passwort vergessen?</a><input type="hidden" name="csrf_token" value="{CSRF}"></div></form>',
        'logout' => '<div class="card mb-4"><h2 class="card-header h6 mb-0">Ausloggen</h2><div class="card-body text-center"><p class="mb-3">Wirklich ausloggen, <strong>{NICKNAME}</strong>?</p><form method="POST" action="user.php?page=logout" class="d-inline"><input type="hidden" name="csrf_token" value="{CSRF}"><button type="submit" class="btn btn-danger me-2">Ja, ausloggen</button></form><a class="btn btn-outline-secondary" href="index.php">Nein, abbrechen</a></div></div>',
        // B22/B44: Link anfordern statt Sofort-Reset
        'senddataform' => '<form accept-charset="UTF-8" action="user.php?page=senddata" method="post" class="card mb-4"><h2 class="card-header h6 mb-0">Passwort vergessen</h2><div class="card-body"><div class="mb-3"><label for="pn_searchstring" class="form-label fw-bold">Nickname oder E-Mail-Adresse</label><input class="form-control" name="pndata[searchstring]" id="pn_searchstring" maxlength="100" required aria-describedby="pn_search_help"><div id="pn_search_help" class="form-text">Per E-Mail kommt ein Link, über den sich ein neues Passwort festlegen lässt. Der Link gilt 60 Minuten; bis dahin bleibt das bisherige Passwort bestehen.</div></div><button type="submit" class="btn btn-primary">Link anfordern</button><input type="hidden" name="csrf_token" value="{CSRF}"></div></form>',
        // B44: ohne ICQ, „Name“ statt „Realname“
        'profileform' => '<form accept-charset="UTF-8" action="user.php?page=profile&pndata[send]=YES" method="post" class="card mb-4"><h2 class="card-header h6 mb-0">Profil editieren</h2><div class="card-body"><div class="mb-3"><label for="pn_nickname" class="form-label fw-bold">Nickname</label><input class="form-control" name="pndata[nickname]" id="pn_nickname" maxlength="30" value="{NICKNAME}" required></div><div class="mb-3"><label for="pn_email" class="form-label fw-bold">E-Mail-Adresse</label><input type="email" class="form-control" name="pndata[email]" id="pn_email" maxlength="100" value="{EMAIL}" required></div><div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="pndata[showemail]" value="YES" id="pn_showemail" {SHOWEMAIL} aria-describedby="pn_showemail_help"><label class="form-check-label" for="pn_showemail">Namen mit E-Mail-Adresse verlinken (öffentlich sichtbar)</label><div id="pn_showemail_help" class="form-text">Der Name unter News und Kommentaren wird dann zum Mail-Link, die Adresse ist für alle Besucher sichtbar.</div></div><div class="row g-2 mb-3"><div class="col-12 col-md-6"><label for="pn_password" class="form-label fw-bold">Neues Passwort</label><input type="password" class="form-control" name="pndata[password]" id="pn_password" value="{PASSWORD}" maxlength="128" autocomplete="new-password"></div><div class="col-12 col-md-6"><label for="pn_password2" class="form-label fw-bold">Passwort wiederholen</label><input type="password" class="form-control" name="pndata[password2]" id="pn_password2" value="{PASSWORD}" maxlength="128" autocomplete="new-password"></div></div><div class="row g-2 mb-3"><div class="col-12 col-md-6"><label for="pn_realname" class="form-label fw-bold">Name</label><input class="form-control" name="pndata[realname]" id="pn_realname" maxlength="100" value="{REALNAME}" autocomplete="name"></div><div class="col-12 col-md-6"><label for="pn_city" class="form-label fw-bold">Wohnort</label><input class="form-control" name="pndata[city]" id="pn_city" maxlength="100" value="{CITY}"></div></div><div class="row g-2 mb-3"><div class="col-12 col-md-3"><label for="pn_age" class="form-label fw-bold">Alter</label><input class="form-control" name="pndata[age]" id="pn_age" maxlength="3" value="{AGE}" inputmode="numeric"></div><div class="col-12 col-md-9"><label for="pn_homepage" class="form-label fw-bold">Homepage</label><input class="form-control" type="url" name="pndata[homepage]" id="pn_homepage" maxlength="250" value="{HOMEPAGE}" placeholder="https://"></div></div><div class="d-flex gap-2"><button type="submit" class="btn btn-primary">Profil speichern</button><button type="reset" class="btn btn-outline-secondary">Zurücksetzen</button></div><input type="hidden" name="csrf_token" value="{CSRF}"></div></form>',
        // B13: Beschriftungen mit for-Attribut für die Auswahlfelder
        'archive' => '<section class="card mb-4"><h2 class="card-header h6 mb-0">Newsarchiv</h2><div class="card-body"><form accept-charset="UTF-8" action="archive.php" method="post" class="row g-2 align-items-end mb-3"><div class="col-12 col-md-4"><label for="pn_showyear" class="form-label small text-muted mb-1">Jahr</label>{SELECTYEAR}</div><div class="col-12 col-md-4"><label for="pn_showmonth" class="form-label small text-muted mb-1">Monat</label>{SELECTMONTH}</div><div class="col-12 col-md-4"><button type="submit" class="btn btn-primary w-100">Anzeigen</button></div></form><div class="text-center small text-muted my-2">oder</div><form accept-charset="UTF-8" action="archive.php?pndata[type]=search" method="post" class="row g-2 align-items-end"><div class="col-12 col-md-9"><label for="pn_searchstring" class="form-label small text-muted mb-1">Suchen nach</label><input class="form-control" name="pndata[searchstring]" id="pn_searchstring" maxlength="50" value="{SEARCHSTRING}"></div><div class="col-12 col-md-3"><button type="submit" class="btn btn-outline-primary w-100">Suchen</button></div></form></div></section>',
        // B15: echte Umlaute
        'sendnewsform' => '<form accept-charset="UTF-8" action="sendnews.php?pndata[send]=YES" method="post" class="card mb-4"><h2 class="card-header h6 mb-0">News einsenden</h2><div class="card-body"><div class="mb-3"><span class="form-label fw-bold d-block">Nickname</span><div class="form-control-plaintext">{USER}</div></div><div class="mb-3"><label for="pn_catid" class="form-label fw-bold">Kategorie</label><div>{CATEGORYSELECT}</div></div><div class="mb-3"><label for="pn_title" class="form-label fw-bold">Titel</label><input class="form-control" name="pndata[title]" id="pn_title" maxlength="150" required></div><div class="mb-3"><label for="pn_text" class="form-label fw-bold">Text</label><textarea class="form-control" name="pndata[text]" id="pn_text" rows="6" required></textarea></div><div class="mb-3"><label for="pn_moretext" class="form-label fw-bold">Langer Text</label><textarea class="form-control" name="pndata[moretext]" id="pn_moretext" rows="6" aria-describedby="pn_moretext_help"></textarea><div id="pn_moretext_help" class="form-text">Optionaler ausführlicher Text, der auf der Detailseite erscheint.</div></div>{RELATEDLINKS}<button type="submit" class="btn btn-primary">News einsenden</button><input type="hidden" name="csrf_token" value="{CSRF}"></div></form>',
        // Mails ohne Passwort und ohne Anrede (passend für Du, Sie und jede Sprache): Einladung
        // mit Link zum Festlegen des Passworts, Registrierung mit Anmeldelink, Datenänderung
        'addemail' => "Hallo {NICKNAME},\n\nauf {SITE} wurde ein Zugang für die E-Mail-Adresse {EMAIL} eingerichtet. Das Passwort lässt sich über diesen Link selbst festlegen:\n\n{INVITELINK}\n\nDer Link ist {VALIDHOURS} Stunden gültig und nur einmal verwendbar. Danach ist die Anmeldung mit dem Nickname „{NICKNAME}“ und dem neuen Passwort möglich:\n\n{LOGINLINK}\n\nIst der Link abgelaufen, lässt sich über „Passwort vergessen“ ein neuer anfordern.\n\nBitte nicht auf diese automatisch generierte E-Mail antworten!",
        'editemail' => "Hallo {NICKNAME},\n\ndie Kontodaten auf {SITE} wurden soeben von einem Administrator geändert. Der aktuelle Stand:\n\nNickname: {NICKNAME}\nE-Mail-Adresse: {EMAIL}\n\nDas Passwort bleibt unverändert.\n\nBitte nicht auf diese automatisch generierte E-Mail antworten!",
        'registeremail' => "Willkommen bei {SITE}, {NICKNAME}!\n\nDie Registrierung ist abgeschlossen. Die Anmeldung ist ab sofort mit dem Nickname „{NICKNAME}“ und dem bei der Registrierung gewählten Passwort möglich:\n\n{LOGINLINK}\n\nHinterlegte E-Mail-Adresse: {EMAIL}\n\nBitte nicht auf diese automatisch generierte E-Mail antworten!",
        'dataemail' => "Hallo {NICKNAME},\n\nfür das Konto „{NICKNAME}“ auf {SITE} wurde ein neues Passwort angefordert. Das neue Passwort lässt sich innerhalb von {VALIDMINUTES} Minuten über diesen Link festlegen:\n\n{RESETLINK}\n\nWurde kein neues Passwort angefordert, diese E-Mail einfach ignorieren. Das bisherige Passwort bleibt gültig.\n\nBitte nicht auf diese automatisch generierte E-Mail antworten!",
    ];
}

/**
 * Fingerabdrücke der Felder des Default-Templates 3.11 (sha1 nach pn_template_normalize()).
 * Nur Felder mit diesem Inhalt gelten als „unverändert“ und werden aktualisiert.
 *
 * @return array<string, string>
 */
function pn_default_template_legacy_hashes(): array
{
    return [
        'message' => '0c678f5972cf0ed55559bca5c9b2dd4453e65b77',
        'headline' => 'ccba75f361d45b4a1cf12883391500745ff7d8ab',
        'news' => '42532fa733813155e61136a547b0b53812c3423f',
        'comment' => 'b23fdb78ed86fe47d8d465fe1c27987688ef5641',
        'usermenu' => '108bc1dd48f6f6b5b3ccab37f10912abdb97d8e0',
        'usermenu2' => '3f97faaff2e275e88247b7e1fbe08c0fdd3a824f',
        'relatedlinks' => '301ebcc9e842214d655ddff63fae5c6ce962461b',
        'commentform' => '0f1f82a489d02aa76b501e5e50626b7112ce56a7',
        'registerform' => 'f2958ab6a04a563d27c989964f866688d5aa20e1',
        'loginform' => 'fb9b03e527b9b8cfdfc888d8c801f8d70442cc33',
        'logout' => '0c06fba071dc6bccbbbfa71504f1dba8e64ad00b',
        'senddataform' => '8d7c3405b5ef2ecceb8fb76028feb49e94aa546e',
        'profileform' => '81eb0b30dd6dcaa7b69235feb80109dc59d6b5fc',
        'archive' => '2efaf89fdee641b7557cce20bd8c6875b3a08952',
        'sendnewsform' => '7cbdbb1467335173736a251271b1532f938a2704',
        'addemail' => 'e7a8f165dc0d972ef27794ccdca7ffec311b415e',
        'editemail' => '2232a21db3bf691283e5a8e3cc172a49d16c8763',
        'registeremail' => '17ad03499b790cdf952e8c15e3072eba49cb9419',
        'dataemail' => '79f015c0193c6c3fcb40f9c36d5c4172bfb4e010',
    ];
}

/**
 * Vergleichsform eines Template-Felds: Zeilenenden vereinheitlicht, Rand-Leerraum entfernt
 * (das Admin-Formular sendet CRLF und kürzt Leerraum).
 */
function pn_template_normalize(string $content): string
{
    return trim(str_replace("\r\n", "\n", $content));
}
