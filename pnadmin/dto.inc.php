<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2024 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

/**
 * Data Transfer Objects (DTOs) für Admin-Funktionen.
 *
 * Diese Klassen gruppieren zusammengehörige Parameter,
 * um die Anzahl der Methodenparameter zu reduzieren.
 */

/**
 * DTO für Template-Daten.
 */
class TemplateData
{
    public function __construct(
        public readonly string $title = '',
        public readonly string $message = '',
        public readonly string $headline = '',
        public readonly string $news = '',
        public readonly string $comment = '',
        public readonly string $usermenu = '',
        public readonly string $usermenu2 = '',
        public readonly string $relatedlinks = '',
        public readonly string $commentform = '',
        public readonly string $registerform = '',
        public readonly string $loginform = '',
        public readonly string $logout = '',
        public readonly string $senddataform = '',
        public readonly string $profileform = '',
        public readonly string $archive = '',
        public readonly string $sendnewsform = '',
        public readonly string $addemail = '',
        public readonly string $editemail = '',
        public readonly string $registeremail = '',
        public readonly string $dataemail = '',
    ) {
    }

    /**
     * Erstellt TemplateData aus POST-Daten.
     */
    public static function fromPost(): self
    {
        return new self(
            title: pn_post_string('title', 10000),
            message: pn_post_string('message', 10000),
            headline: pn_post_string('headline', 10000),
            news: pn_post_string('news', 10000),
            comment: pn_post_string('comment', 10000),
            usermenu: pn_post_string('usermenu', 10000),
            usermenu2: pn_post_string('usermenu2', 10000),
            relatedlinks: pn_post_string('relatedlinks', 10000),
            commentform: pn_post_string('commentform', 10000),
            registerform: pn_post_string('registerform', 10000),
            loginform: pn_post_string('loginform', 10000),
            logout: pn_post_string('logout', 10000),
            senddataform: pn_post_string('senddataform', 10000),
            profileform: pn_post_string('profileform', 10000),
            archive: pn_post_string('archive', 10000),
            sendnewsform: pn_post_string('sendnewsform', 10000),
            addemail: pn_post_string('addemail', 10000),
            editemail: pn_post_string('editemail', 10000),
            registeremail: pn_post_string('registeremail', 10000),
            dataemail: pn_post_string('dataemail', 10000),
        );
    }

    /**
     * Prüft ob alle erforderlichen Felder ausgefüllt sind.
     */
    public function isValid(): bool
    {
        return $this->title !== ''
            && $this->message !== ''
            && $this->headline !== ''
            && $this->news !== ''
            && $this->comment !== ''
            && $this->usermenu !== ''
            && $this->usermenu2 !== ''
            && $this->relatedlinks !== ''
            && $this->commentform !== ''
            && $this->registerform !== ''
            && $this->loginform !== ''
            && $this->logout !== ''
            && $this->senddataform !== ''
            && $this->profileform !== ''
            && $this->archive !== ''
            && $this->sendnewsform !== ''
            && $this->addemail !== ''
            && $this->editemail !== ''
            && $this->registeremail !== ''
            && $this->dataemail !== '';
    }
}

/**
 * DTO für Berechtigungsdaten.
 */
class PermissionsData
{
    public function __construct(
        public readonly string $canreadtemplates = 'NO',
        public readonly string $canwritetemplates = 'NO',
        public readonly string $canreadconfig = 'NO',
        public readonly string $canwriteconfig = 'NO',
        public readonly string $canreadusers = 'NO',
        public readonly string $canwriteusers = 'NO',
        public readonly string $canreadpermissions = 'NO',
        public readonly string $canwritepermissions = 'NO',
        public readonly string $canreadcategories = 'NO',
        public readonly string $canwritecategories = 'NO',
        public readonly string $canreadnews = 'NO',
        public readonly string $canwritenews = 'NO',
        public readonly string $canreadcomments = 'NO',
        public readonly string $canwritecomments = 'NO',
    ) {
    }

    /**
     * Erstellt PermissionsData aus POST-Daten.
     */
    public static function fromPost(): self
    {
        return new self(
            canreadtemplates: pn_validate_yesno($_POST['canreadtemplates'] ?? null),
            canwritetemplates: pn_validate_yesno($_POST['canwritetemplates'] ?? null),
            canreadconfig: pn_validate_yesno($_POST['canreadconfig'] ?? null),
            canwriteconfig: pn_validate_yesno($_POST['canwriteconfig'] ?? null),
            canreadusers: pn_validate_yesno($_POST['canreadusers'] ?? null),
            canwriteusers: pn_validate_yesno($_POST['canwriteusers'] ?? null),
            canreadpermissions: pn_validate_yesno($_POST['canreadpermissions'] ?? null),
            canwritepermissions: pn_validate_yesno($_POST['canwritepermissions'] ?? null),
            canreadcategories: pn_validate_yesno($_POST['canreadcategories'] ?? null),
            canwritecategories: pn_validate_yesno($_POST['canwritecategories'] ?? null),
            canreadnews: pn_validate_yesno($_POST['canreadnews'] ?? null),
            canwritenews: pn_validate_yesno($_POST['canwritenews'] ?? null),
            canreadcomments: pn_validate_yesno($_POST['canreadcomments'] ?? null),
            canwritecomments: pn_validate_yesno($_POST['canwritecomments'] ?? null),
        );
    }

    /**
     * Konvertiert zu Array für DB-Operationen.
     */
    public function toArray(): array
    {
        return [
            'canreadtemplates' => $this->canreadtemplates,
            'canwritetemplates' => $this->canwritetemplates,
            'canreadconfig' => $this->canreadconfig,
            'canwriteconfig' => $this->canwriteconfig,
            'canreadusers' => $this->canreadusers,
            'canwriteusers' => $this->canwriteusers,
            'canreadpermissions' => $this->canreadpermissions,
            'canwritepermissions' => $this->canwritepermissions,
            'canreadcategories' => $this->canreadcategories,
            'canwritecategories' => $this->canwritecategories,
            'canreadnews' => $this->canreadnews,
            'canwritenews' => $this->canwritenews,
            'canreadcomments' => $this->canreadcomments,
            'canwritecomments' => $this->canwritecomments,
        ];
    }
}

/**
 * DTO für Konfigurationsdaten.
 */
class ConfigData
{
    public function __construct(
        public readonly string $categories = 'NO',
        public readonly string $categorypics = 'NO',
        public readonly string $comments = 'NO',
        public readonly string $commentwriting = 'Guests & Registered',
        public readonly string $moretext = 'NO',
        public readonly string $sendnews = 'NO',
        public readonly string $newssending = 'Guests & Registered',
        public readonly string $smilies = 'NO',
        public readonly string $bbcode = 'NO',
        public readonly string $html = 'NO',
        public readonly string $dateformat = 'd.m.Y',
        public readonly string $timeformat = 'H:i',
        public readonly int $template = 1,
        public readonly string $url = '',
        public readonly string $email = '',
        public readonly int $headlines = 5,
        public readonly int $news = 5,
        public readonly int $spamprotection = 60,
        public readonly string $relatedlinks = 'NO',
        public readonly int $relatedlinks_num = 3,
    ) {
    }

    /**
     * Erstellt ConfigData aus POST-Daten.
     */
    public static function fromPost(): self
    {
        return new self(
            categories: pn_validate_yesno($_POST['categories'] ?? null),
            categorypics: pn_validate_yesno($_POST['categorypics'] ?? null),
            comments: pn_validate_yesno($_POST['comments'] ?? null),
            commentwriting: pn_validate_whitelist(
                $_POST['commentwriting'] ?? null,
                ['Guests & Registered', 'Registered'],
                'Guests & Registered',
            ),
            moretext: pn_validate_yesno($_POST['moretext'] ?? null),
            sendnews: pn_validate_yesno($_POST['sendnews'] ?? null),
            newssending: pn_validate_whitelist(
                $_POST['newssending'] ?? null,
                ['Guests & Registered', 'Registered'],
                'Guests & Registered',
            ),
            smilies: pn_validate_yesno($_POST['smilies'] ?? null),
            bbcode: pn_validate_yesno($_POST['bbcode'] ?? null),
            html: pn_validate_yesno($_POST['html'] ?? null),
            dateformat: pn_post_string('dateformat', 20),
            timeformat: pn_post_string('timeformat', 20),
            template: pn_post_id('template'),
            url: pn_validate_url($_POST['url'] ?? null),
            email: pn_validate_email($_POST['email'] ?? null),
            headlines: pn_validate_int_range($_POST['headlines'] ?? null, 1, 100, 5),
            news: pn_validate_int_range($_POST['news'] ?? null, 1, 100, 5),
            spamprotection: pn_validate_int_range($_POST['spamprotection'] ?? null, 0, 86400, 60),
            relatedlinks: pn_validate_yesno($_POST['relatedlinks'] ?? null),
            relatedlinks_num: pn_validate_int_range($_POST['relatedlinks_num'] ?? null, 1, 20, 3),
        );
    }

    /**
     * Validiert Email und URL.
     */
    public function isValid(): bool
    {
        return $this->email !== '' && $this->url !== '';
    }
}
