<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

namespace PowerNews;

/**
 * Kopfzeilen und Nachrichtentext einer PowerNews-Mail.
 *
 * From ist die Absenderadresse aus der Konfiguration (mit Absendername), Reply-To
 * dieselbe Adresse – Antworten landen so auch dann dort, wenn der Mailserver des
 * Hosters den Absender umschreibt. Betreff und Namen werden nach RFC 2047 kodiert, der
 * Text ist UTF-8. Steuerzeichen in Betreff, Namen und Adressen fallen weg, damit keine
 * Kopfzeilen eingeschleust werden können.
 */
final class MailMessage
{
    /** Höchstlänge einer Kopfzeile mit kodierten Wörtern (RFC 2047, 2). */
    public const int LINE_MAX = 76;

    public const string ENCODING_8BIT = '8bit';

    public const string ENCODING_QUOTED_PRINTABLE = 'quoted-printable';

    /**
     * Kopfzeilen für PHPs mail() – ohne To und Subject, die mail() selbst setzt.
     *
     * @return array<string, string>
     */
    public static function headers(string $fromName, string $fromAddress): array
    {
        return [
            'From' => self::formatAddress($fromName, $fromAddress, strlen('From: ')),
            'Reply-To' => self::cleanAddress($fromAddress),
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Transfer-Encoding' => self::ENCODING_8BIT,
        ];
    }

    /**
     * Vollständige Nachricht (Kopfzeilen und Text, CRLF) für den SMTP-Versand.
     *
     * @param string $transferEncoding ENCODING_8BIT (Server kann 8BITMIME) oder ENCODING_QUOTED_PRINTABLE
     */
    public static function build(
        string $to,
        string $subject,
        string $body,
        string $fromAddress,
        string $fromName,
        string $transferEncoding,
    ): string {
        $body = self::lineEndings($body);
        $domain = substr((string) strrchr(self::cleanAddress($fromAddress), '@'), 1);
        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . self::formatAddress($fromName, $fromAddress, strlen('From: ')),
            'To: ' . self::cleanAddress($to),
            'Reply-To: ' . self::cleanAddress($fromAddress),
            'Subject: ' . self::encodeHeader($subject, strlen('Subject: ')),
            'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . ($domain !== '' ? $domain : 'powernews.invalid') . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
        ];

        if ($transferEncoding === self::ENCODING_QUOTED_PRINTABLE) {
            $headers[] = 'Content-Transfer-Encoding: ' . self::ENCODING_QUOTED_PRINTABLE;
            $body = quoted_printable_encode($body);
        } else {
            $headers[] = 'Content-Transfer-Encoding: ' . self::ENCODING_8BIT;
        }

        return implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n";
    }

    /**
     * Adresse mit Namen für From: ASCII-Namen mit Sonderzeichen in Anführungszeichen,
     * alle anderen nach RFC 2047 kodiert. Steuerzeichen (Zeilenumbrüche) fallen weg.
     *
     * @param int $offset Länge des Feldnamens samt „: “ (für die Zeilenlänge)
     */
    public static function formatAddress(string $name, string $address, int $offset = 0): string
    {
        $name = self::singleLine($name);
        $address = self::cleanAddress($address);

        if ($name === '') {
            return $address;
        }

        if (preg_match('/^[\x20-\x7E]*$/', $name) !== 1 || str_contains($name, '=?')) {
            return self::encodeHeader($name, $offset) . ' <' . $address . '>';
        }

        if (preg_match('/[()<>\[\]:;@\\\\,."]/', $name) === 1) {
            $name = '"' . addcslashes($name, '"\\') . '"';
        }

        return $name . ' <' . $address . '>';
    }

    /**
     * Kodiert einen Kopfzeilen-Text nach RFC 2047 (Base64, UTF-8), wenn er Nicht-ASCII
     * enthält oder zu lang ist. Kodierte Wörter werden an Zeichengrenzen getrennt und
     * mit CRLF + Leerzeichen gefaltet, sodass keine Zeile länger als 76 Zeichen wird.
     *
     * @param int $offset Länge des Feldnamens samt „: “ in der ersten Zeile
     */
    public static function encodeHeader(string $text, int $offset = 0): string
    {
        $text = self::singleLine($text);

        if (preg_match('/^[\x20-\x7E]*$/', $text) === 1 && !str_contains($text, '=?') && $offset + strlen($text) <= self::LINE_MAX) {
            return $text;
        }

        $words = [];
        $chunk = '';
        $room = self::wordBytes(self::LINE_MAX - $offset);

        foreach (mb_str_split($text, 1, 'UTF-8') as $char) {
            if ($chunk !== '' && strlen($chunk . $char) > $room) {
                $words[] = '=?UTF-8?B?' . base64_encode($chunk) . '?=';
                $chunk = '';
                $room = self::wordBytes(self::LINE_MAX - 1);
            }
            $chunk .= $char;
        }

        $words[] = '=?UTF-8?B?' . base64_encode($chunk) . '?=';

        return implode("\r\n ", $words);
    }

    /**
     * SMTP-Punktverdopplung (RFC 5321, 4.5.2): Zeilen, die mit „.“ beginnen, bekommen
     * einen zweiten Punkt. Sonst beendet eine Zeile „.“ im Text die Nachricht
     * vorzeitig, und alles danach würde der Server als SMTP-Befehl ausführen.
     */
    public static function dotStuff(string $message): string
    {
        return (string) preg_replace('/^\./m', '..', $message);
    }

    /**
     * Adresse ohne Leer- und Steuerzeichen und ohne spitze Klammern.
     */
    public static function cleanAddress(string $address): string
    {
        return (string) preg_replace('/[\x00-\x20\x7F<>]+/', '', $address);
    }

    /**
     * Einheitliche Zeilenenden CRLF.
     */
    public static function lineEndings(string $body): string
    {
        return (string) preg_replace("/\r\n|\r|\n/", "\r\n", $body);
    }

    /**
     * Rohe Bytes je kodiertem Wort, damit „=?UTF-8?B?…?=“ in $columns Zeichen passt.
     */
    private static function wordBytes(int $columns): int
    {
        return max(3, intdiv($columns - strlen('=?UTF-8?B??='), 4) * 3);
    }

    private static function singleLine(string $text): string
    {
        return trim((string) preg_replace('/[\x00-\x1F\x7F]+/', ' ', mb_scrub($text, 'UTF-8')));
    }
}
