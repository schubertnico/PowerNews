<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

namespace PowerNews\Installer;

use PowerNews\LocalConfig;
use PowerNews\Mailer;

/**
 * „Test-Mail senden“ im Installer-Schritt „Website“: prüft den eingestellten
 * Mailversand, bevor PowerNews eingerichtet wird, und meldet ehrlich, was dabei
 * herauskam.
 *
 * @phpstan-import-type MailConfig from LocalConfig
 */
final class SmtpCheck
{
    public const string SUBJECT = 'PowerNews: Test-Mail aus dem Installer';

    /**
     * Beschriftung der Versandarten (Auswahlfeld #mail_transport).
     */
    public const array TRANSPORTS = [
        Mailer::TRANSPORT_MAIL => 'PHP-mail des Servers',
        Mailer::TRANSPORT_SMTP => 'SMTP-Server',
    ];

    /**
     * Beschriftung der Verschlüsselungen (Auswahlfeld #smtp_encryption).
     */
    public const array ENCRYPTIONS = [
        Mailer::ENCRYPTION_NONE => 'Keine',
        Mailer::ENCRYPTION_STARTTLS => 'STARTTLS (meist Port 587)',
        Mailer::ENCRYPTION_SSL => 'SSL/TLS (meist Port 465)',
    ];

    /**
     * Kurzbezeichnung der Verschlüsselung für Zusammenfassungen.
     */
    private const array ENCRYPTION_NAMES = [
        Mailer::ENCRYPTION_NONE => 'ohne Verschlüsselung',
        Mailer::ENCRYPTION_STARTTLS => 'STARTTLS',
        Mailer::ENCRYPTION_SSL => 'SSL/TLS',
    ];

    /**
     * Einstellungen in einer Zeile, ohne Passwort – z. B. „PHP-mail des Servers“ oder
     * „SMTP-Server smtp.example.org:587, STARTTLS, Anmeldung als news@example.org“.
     *
     * @param MailConfig $mail
     */
    public static function describe(#[\SensitiveParameter] array $mail): string
    {
        if ($mail['transport'] !== Mailer::TRANSPORT_SMTP) {
            return self::TRANSPORTS[Mailer::TRANSPORT_MAIL];
        }

        return self::TRANSPORTS[Mailer::TRANSPORT_SMTP] . ' ' . $mail['host'] . ':' . Mailer::effectivePort($mail['port'], $mail['encryption'])
            . ', ' . (self::ENCRYPTION_NAMES[$mail['encryption']] ?? $mail['encryption'])
            . ', ' . ($mail['user'] !== '' ? 'Anmeldung als ' . $mail['user'] : 'ohne Anmeldung');
    }

    /**
     * @param MailConfig $mail
     */
    public static function body(#[\SensitiveParameter] array $mail, string $sender): string
    {
        return "Hallo,\n\n"
            . "diese Nachricht hat der Installer von PowerNews verschickt, um den E-Mail-Versand zu prüfen.\n"
            . "Wenn sie angekommen ist, stimmen die Einstellungen.\n\n"
            . 'Versand: ' . self::describe($mail) . "\n"
            . 'Absender: ' . $sender . "\n\n"
            . "Sie müssen nichts weiter tun.\n";
    }

    /**
     * Schickt die Test-Mail mit einem Mailer aus der Konfiguration $mail.
     *
     * @param MailConfig $mail
     *
     * @return array{ok: bool, message: string}
     */
    public static function run(#[\SensitiveParameter] array $mail, string $recipient, string $sender): array
    {
        return self::send(Mailer::fromConfig($mail), $mail, $recipient, $sender);
    }

    /**
     * Schickt die Test-Mail an $recipient – mit demselben Absender (From, Reply-To)
     * wie alle Mails von PowerNews. Erfolg heißt: mail() bzw. der Mailserver hat sie
     * angenommen – ob sie ankommt, zeigt erst das Postfach.
     *
     * @param MailConfig $mail
     *
     * @return array{ok: bool, message: string}
     */
    public static function send(Mailer $mailer, #[\SensitiveParameter] array $mail, string $recipient, string $sender): array
    {
        if (!$mailer->send($recipient, self::SUBJECT, self::body($mail, $sender), $sender, 'PowerNews')) {
            return [
                'ok' => false,
                'message' => 'Die Test-Mail an ' . $recipient . ' konnte nicht verschickt werden. ' . $mailer->lastError(),
            ];
        }

        $accepted = $mail['transport'] === Mailer::TRANSPORT_SMTP
            ? 'Der Mailserver hat die Test-Mail an ' . $recipient . ' angenommen.'
            : 'Die PHP-Funktion mail() hat die Test-Mail an ' . $recipient . ' übernommen.';

        return [
            'ok' => true,
            'message' => $accepted . ' Bitte sehen Sie im Postfach nach (auch im Spam-Ordner) – erst dort zeigt sich, ob sie wirklich ankommt.',
        ];
    }
}
