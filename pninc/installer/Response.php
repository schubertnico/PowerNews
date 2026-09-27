<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

namespace PowerNews\Installer;

/**
 * Ergebnis einer Anfrage an den Installer: eine Seite, eine Weiterleitung
 * (Post/Redirect/Get) oder ein Download. Ausgegeben wird es von
 * Installer::emit(), damit der Controller ohne Ausgabe testbar bleibt.
 */
final readonly class Response
{
    public const string PAGE = 'page';

    public const string REDIRECT = 'redirect';

    public const string DOWNLOAD = 'download';

    /**
     * @param array<string, mixed> $args benannte Argumente für das Template
     */
    private function __construct(
        public string $kind,
        public int $status,
        public string $template,
        public string $title,
        public int $step,
        public array $args,
        public string $location,
        public string $content,
        public bool $renewSession,
    ) {
    }

    /**
     * @param array<string, mixed> $args
     */
    public static function page(string $template, string $title, int $step, array $args, int $status): self
    {
        return new self(self::PAGE, $status, $template, $title, $step, $args, '', '', false);
    }

    public static function redirect(string $location): self
    {
        return new self(self::REDIRECT, 303, '', '', 0, [], $location, '', false);
    }

    /**
     * Weiterleitung nach abgeschlossener Installation: neue Session-ID und
     * neues CSRF-Token.
     */
    public static function redirectWithNewSession(string $location): self
    {
        return new self(self::REDIRECT, 303, '', '', 0, [], $location, '', true);
    }

    /**
     * config.local.php zum Herunterladen.
     */
    public static function download(string $filename, #[\SensitiveParameter] string $content): self
    {
        return new self(self::DOWNLOAD, 200, '', $filename, 0, [], '', $content, false);
    }
}
