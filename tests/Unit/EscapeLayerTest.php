<?php
declare(strict_types=1);

namespace PowerNews\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

// Include the escape layer
require_once __DIR__ . '/../../pninc/escape.inc.php';

/**
 * Tests for the escape layer functions
 */
class EscapeLayerTest extends TestCase
{
    // === pn_html() Tests ===

    #[Test]
    public function htmlEscapesSpecialCharacters(): void
    {
        $this->assertEquals('&lt;script&gt;', pn_html('<script>'));
        $this->assertEquals('&amp;', pn_html('&'));
        $this->assertEquals('&quot;', pn_html('"'));
        // ENT_HTML5 uses &apos; for single quotes
        $this->assertEquals('&apos;', pn_html("'"));
    }

    #[Test]
    public function htmlHandlesNull(): void
    {
        $this->assertEquals('', pn_html(null));
    }

    #[Test]
    public function htmlPreservesNormalText(): void
    {
        $this->assertEquals('Hello World', pn_html('Hello World'));
        $this->assertEquals('123', pn_html('123'));
    }

    #[Test]
    public function htmlPreservesUnicode(): void
    {
        $this->assertEquals('äöüß', pn_html('äöüß'));
        $this->assertEquals('日本語', pn_html('日本語'));
    }

    #[Test]
    #[DataProvider('xssPayloadProvider')]
    public function htmlBlocksXSSPayloads(string $payload): void
    {
        $escaped = pn_html($payload);
        // HTML escaping prevents browser from executing scripts
        $this->assertStringNotContainsString('<script', $escaped);
        // Note: javascript: is preserved as text but < and > are escaped, preventing execution
    }

    public static function xssPayloadProvider(): array
    {
        return [
            'script tag' => ['<script>alert(1)</script>'],
            'img onerror' => ['<img src=x onerror=alert(1)>'],
            'svg onload' => ['<svg onload=alert(1)>'],
            'javascript url' => ['<a href="javascript:alert(1)">'],
        ];
    }

    // === pn_attr() Tests ===

    #[Test]
    public function attrEscapesQuotes(): void
    {
        $this->assertEquals('value&quot;with&quot;quotes', pn_attr('value"with"quotes'));
        // ENT_HTML5 uses &apos; for single quotes
        $this->assertEquals('value&apos;with&apos;quotes', pn_attr("value'with'quotes"));
    }

    #[Test]
    public function attrHandlesBreakoutAttempts(): void
    {
        $escaped = pn_attr('"><script>alert(1)</script><input value="');
        $this->assertStringNotContainsString('<script>', $escaped);
        $this->assertStringContainsString('&quot;', $escaped);
    }

    // === pn_js() Tests ===

    #[Test]
    public function jsReturnsJsonEncodedString(): void
    {
        $this->assertEquals('"hello"', pn_js('hello'));
        $this->assertEquals('"test\u0027s"', pn_js("test's"));
    }

    #[Test]
    public function jsHandlesNull(): void
    {
        $this->assertEquals('""', pn_js(null));
    }

    #[Test]
    public function jsEscapesSpecialCharacters(): void
    {
        $escaped = pn_js('</script>');
        $this->assertStringNotContainsString('</script>', $escaped);
    }

    // === pn_url() Tests ===

    #[Test]
    public function urlEncodesSpecialCharacters(): void
    {
        $this->assertEquals('hello%20world', pn_url('hello world'));
        $this->assertEquals('a%26b', pn_url('a&b'));
        $this->assertEquals('test%3Dvalue', pn_url('test=value'));
    }

    #[Test]
    public function urlHandlesNull(): void
    {
        $this->assertEquals('', pn_url(null));
    }

    // === pn_css() Tests ===

    #[Test]
    public function cssAllowsSafeValues(): void
    {
        $this->assertEquals('#ff0000', pn_css('#ff0000'));
        $this->assertEquals('100%', pn_css('100%'));
        $this->assertEquals('10px', pn_css('10px'));
    }

    #[Test]
    public function cssRemovesDangerousValues(): void
    {
        // pn_css only allows alphanumeric + safe chars, removes special chars
        $escaped = pn_css('<script>');
        $this->assertStringNotContainsString('<', $escaped);
        $this->assertStringNotContainsString('>', $escaped);

        // Colon and semicolon are removed
        $escaped = pn_css('expression:url;');
        $this->assertStringNotContainsString(':', $escaped);
        $this->assertStringNotContainsString(';', $escaped);
    }

    // === pn_int() Tests ===

    #[Test]
    public function intCastsToInteger(): void
    {
        $this->assertEquals(42, pn_int(42));
        $this->assertEquals(42, pn_int('42'));
        $this->assertEquals(0, pn_int('abc'));
        $this->assertEquals(0, pn_int(null));
    }

    // === pn_filename() Tests ===

    #[Test]
    public function filenameRemovesPathTraversal(): void
    {
        $this->assertEquals('file.txt', pn_filename('../../../file.txt'));
        $this->assertEquals('file.txt', pn_filename('/etc/passwd/../file.txt'));
    }

    #[Test]
    public function filenameRemovesDangerousCharacters(): void
    {
        // Each dangerous char becomes underscore
        $escaped = pn_filename('file<name>.txt');
        $this->assertStringNotContainsString('<', $escaped);
        $this->assertStringNotContainsString('>', $escaped);

        $escaped = pn_filename('file|name.txt');
        $this->assertStringNotContainsString('|', $escaped);
    }

    #[Test]
    public function filenameRemovesHiddenFiles(): void
    {
        $this->assertEquals('htaccess', pn_filename('.htaccess'));
    }

    #[Test]
    public function filenameHandlesNull(): void
    {
        $this->assertEquals('', pn_filename(null));
    }

    // === e() shortcut Tests ===

    #[Test]
    public function eIsAliasForPnHtml(): void
    {
        $this->assertEquals(pn_html('<script>'), e('<script>'));
        $this->assertEquals(pn_html('test'), e('test'));
    }

    // === pn_is_dangerous() Tests ===

    #[Test]
    public function isDangerousDetectsXSSPatterns(): void
    {
        $this->assertTrue(pn_is_dangerous('<script>alert(1)</script>'));
        $this->assertTrue(pn_is_dangerous('javascript:alert(1)'));
        $this->assertTrue(pn_is_dangerous('<img onerror=alert(1)>'));
        $this->assertTrue(pn_is_dangerous('<iframe src="evil.com">'));
    }

    #[Test]
    public function isDangerousAcceptsSafeStrings(): void
    {
        $this->assertFalse(pn_is_dangerous('Hello World'));
        $this->assertFalse(pn_is_dangerous('Normal text here'));
        $this->assertFalse(pn_is_dangerous('123456'));
    }

    // === pn_strip_tags() Tests ===

    #[Test]
    public function stripTagsRemovesAllTags(): void
    {
        $this->assertEquals('Hello World', pn_strip_tags('<p>Hello <b>World</b></p>'));
    }

    #[Test]
    public function stripTagsAllowsSpecifiedTags(): void
    {
        $result = pn_strip_tags('<p>Hello <b>World</b></p>', ['b']);
        $this->assertEquals('Hello <b>World</b>', $result);
    }

    #[Test]
    public function stripTagsHandlesNull(): void
    {
        $this->assertEquals('', pn_strip_tags(null));
    }
}
