<?php

declare(strict_types=1);

namespace PowerNews\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PowerNews\MailMessage;

require_once __DIR__ . '/../../pninc/mailmessage.inc.php';

/**
 * Kopfzeilen und Nachrichtentext: RFC 2047, Zeilenlängen, Absender, Schutz vor
 * eingeschleusten Kopfzeilen und SMTP-Befehlen.
 */
final class MailMessageTest extends TestCase
{
    #[Test]
    public function headersForPhpMailDeclareUtf8AndSetFromAndReplyTo(): void
    {
        $headers = MailMessage::headers('PowerNews Müller', 'news@example.org');

        $this->assertSame(['From', 'Reply-To', 'MIME-Version', 'Content-Type', 'Content-Transfer-Encoding'], array_keys($headers));
        $this->assertStringStartsWith('=?UTF-8?B?', $headers['From']);
        $this->assertSame('PowerNews Müller <news@example.org>', mb_decode_mimeheader($headers['From']));
        $this->assertSame('news@example.org', $headers['Reply-To']);
        $this->assertSame('text/plain; charset=UTF-8', $headers['Content-Type']);
    }

    #[Test]
    public function builtMessageHasAllHeadersAndCrlfLineEndings(): void
    {
        $message = MailMessage::build('max@example.org', 'Willkommen', "Zeile 1\nZeile 2\rZeile 3", 'news@example.org', 'PowerNews', MailMessage::ENCODING_8BIT);

        [$head, $body] = explode("\r\n\r\n", $message, 2);
        $this->assertMatchesRegularExpression('/^Date: /', $head);
        $this->assertStringContainsString("\r\nFrom: PowerNews <news@example.org>\r\nTo: max@example.org\r\nReply-To: news@example.org\r\nSubject: Willkommen\r\n", $head);
        $this->assertMatchesRegularExpression('/\r\nMessage-ID: <[0-9a-f]{32}@example\.org>\r\n/', $head);
        $this->assertStringEndsWith("MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit", $head);
        $this->assertSame("Zeile 1\r\nZeile 2\r\nZeile 3\r\n", $body);
    }

    #[Test]
    public function quotedPrintableKeepsTheTextSevenBitClean(): void
    {
        $message = MailMessage::build('max@example.org', 's', "Grüße\n" . str_repeat('ä', 60), 'news@example.org', '', MailMessage::ENCODING_QUOTED_PRINTABLE);

        [$head, $body] = explode("\r\n\r\n", $message, 2);
        $this->assertStringContainsString('Content-Transfer-Encoding: quoted-printable', $head);
        $this->assertStringContainsString("From: news@example.org\r\n", $head, 'Ohne Namen nur die Adresse');
        $this->assertMatchesRegularExpression('/^[\x20-\x7E\r\n]*$/', $body);
        foreach (explode("\r\n", $body) as $line) {
            $this->assertLessThanOrEqual(76, strlen($line));
        }
        $this->assertSame("Grüße\r\n" . str_repeat('ä', 60) . "\r\n", quoted_printable_decode($body));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function subjects(): iterable
    {
        yield 'Umlaute' => ['Grüße aus PowerNews'];
        yield 'lang und mit Umlauten' => [str_repeat('Überschrift mit Ärger und Öl ', 8)];
        yield 'nur ASCII, aber lang' => [str_repeat('PowerNews notification ', 6)];
        yield 'Emoji an der Wortgrenze' => [str_repeat('a', 37) . '😀😀😀' . str_repeat('ü', 30)];
        yield 'sieht aus wie ein kodiertes Wort' => ['=?UTF-8?B?SGFsbG8=?='];
    }

    #[Test]
    #[DataProvider('subjects')]
    public function encodedHeadersDecodeBackAndRespectTheLineLength(string $subject): void
    {
        $encoded = MailMessage::encodeHeader($subject, strlen('Subject: '));

        $this->assertSame(trim($subject), mb_decode_mimeheader($encoded));
        $this->assertMatchesRegularExpression('/^[\x20-\x7E\r\n]*$/', $encoded);
        foreach (explode("\r\n", 'Subject: ' . $encoded) as $line) {
            $this->assertLessThanOrEqual(MailMessage::LINE_MAX, strlen($line), $line);
        }
    }

    #[Test]
    public function shortAsciiHeadersStayReadable(): void
    {
        $this->assertSame('PowerNews-Benachrichtigung', MailMessage::encodeHeader('PowerNews-Benachrichtigung', 9));
    }

    #[Test]
    public function lineBreaksCannotInjectHeaders(): void
    {
        $message = MailMessage::build('a@example.org', "Hallo\r\nBcc: opfer@example.org", 'b', 'news@example.org', "Name\r\nBcc: x@example.org", MailMessage::ENCODING_8BIT);
        [$head] = explode("\r\n\r\n", $message, 2);

        $this->assertStringNotContainsString("\r\nBcc:", $head);
        $this->assertStringNotContainsString("\n", MailMessage::headers("Name\r\nBcc: x@example.org", 'news@example.org')['From']);
        $this->assertSame('news@example.org', MailMessage::cleanAddress("news@example.org\r\n"));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function names(): iterable
    {
        yield 'einfach' => ['PowerNews', 'PowerNews <news@example.org>'];
        yield 'leer' => ['', 'news@example.org'];
        yield 'Komma' => ['News, Redaktion', '"News, Redaktion" <news@example.org>'];
        yield 'Anführungszeichen' => ['Die "News"', '"Die \\"News\\"" <news@example.org>'];
        yield 'Punkt' => ['PowerNews e.V.', '"PowerNews e.V." <news@example.org>'];
    }

    #[Test]
    #[DataProvider('names')]
    public function namesAreQuotedWhenNeeded(string $name, string $expected): void
    {
        $this->assertSame($expected, MailMessage::formatAddress($name, 'news@example.org'));
    }

    #[Test]
    public function dotStuffingPreventsSmtpCommandInjection(): void
    {
        $message = MailMessage::build('a@example.org', 's', "Hallo\n.\nRCPT TO:<opfer@example.org>\n.Punkt", 'b@example.org', '', MailMessage::ENCODING_8BIT);
        $stuffed = MailMessage::dotStuff($message);

        $this->assertStringNotContainsString("\r\n.\r\n", $stuffed);
        $this->assertStringContainsString("\r\n..\r\nRCPT TO:<opfer@example.org>\r\n..Punkt", $stuffed);
    }
}
