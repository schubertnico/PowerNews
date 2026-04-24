<?php
declare(strict_types=1);

namespace PowerNews\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for security hardening helpers introduced in the 2026-04-24 fix-sweep:
 *  - pn_validate_nickname (whitelist-based nickname validation)
 *  - pn_csrf_token / pn_csrf_verify (session-bound CSRF tokens)
 *
 * Session state is manipulated explicitly via $_SESSION so these unit tests
 * run deterministically even when PHP's own session handling cannot write
 * to disk (CLI / test runner / headers-already-sent scenarios).
 */
class SecurityHardeningTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset session state before each test so CSRF tokens do not bleed
        // across tests. We do not rely on session_start() succeeding because
        // PHPUnit often has output before the test runs.
        $_SESSION = [];
        $_SESSION['pn_csrf_token'] = '';
    }

    // === pn_validate_nickname() ===

    #[Test]
    public function validateNicknameRejectsEmptyString(): void
    {
        $this->assertSame('', pn_validate_nickname(''));
    }

    #[Test]
    public function validateNicknameRejectsWhitespaceOnly(): void
    {
        $this->assertSame('', pn_validate_nickname('   '));
        $this->assertSame('', pn_validate_nickname("\t\t"));
    }

    #[Test]
    public function validateNicknameRejectsScriptPayload(): void
    {
        $this->assertSame('', pn_validate_nickname('<script>alert(1)</script>'));
    }

    #[Test]
    public function validateNicknameRejectsTooLongInput(): void
    {
        $tooLong = str_repeat('a', 31);
        $this->assertSame('', pn_validate_nickname($tooLong));
    }

    #[Test]
    public function validateNicknameRejectsTooShortInput(): void
    {
        $this->assertSame('', pn_validate_nickname('ab'));
    }

    #[Test]
    public function validateNicknameAcceptsAllowedCharacters(): void
    {
        $this->assertSame('validUser.123', pn_validate_nickname('validUser.123'));
        $this->assertSame('user_name', pn_validate_nickname('user_name'));
        $this->assertSame('user-name', pn_validate_nickname('user-name'));
    }

    #[Test]
    public function validateNicknameAcceptsGermanUmlauts(): void
    {
        $this->assertSame('Müller', pn_validate_nickname('Müller'));
        $this->assertSame('Mäuschen', pn_validate_nickname('Mäuschen'));
        $this->assertSame('Straße', pn_validate_nickname('Straße'));
    }

    #[Test]
    public function validateNicknameAcceptsExactThirtyCharacters(): void
    {
        $thirtyChars = str_repeat('a', 30);
        $this->assertSame($thirtyChars, pn_validate_nickname($thirtyChars));
    }

    #[Test]
    public function validateNicknameHandlesNullInput(): void
    {
        $this->assertSame('', pn_validate_nickname(null));
    }

    #[Test]
    public function validateNicknameTrimsSurroundingWhitespace(): void
    {
        $this->assertSame('validUser', pn_validate_nickname('  validUser  '));
    }

    // === pn_csrf_token() / pn_csrf_verify() ===

    #[Test]
    public function csrfTokenIsSixtyFourCharHex(): void
    {
        // Force generation by clearing any stored token.
        $_SESSION['pn_csrf_token'] = '';
        $token = pn_csrf_token();

        $this->assertIsString($token);
        $this->assertSame(64, strlen($token));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }

    #[Test]
    public function csrfTokenIsStableWithinSession(): void
    {
        $_SESSION['pn_csrf_token'] = '';
        $first = pn_csrf_token();
        $second = pn_csrf_token();

        $this->assertSame($first, $second);
    }

    #[Test]
    public function csrfVerifyRejectsNull(): void
    {
        $_SESSION['pn_csrf_token'] = str_repeat('a', 64);
        $this->assertFalse(pn_csrf_verify(null));
    }

    #[Test]
    public function csrfVerifyRejectsEmptyString(): void
    {
        $_SESSION['pn_csrf_token'] = str_repeat('a', 64);
        $this->assertFalse(pn_csrf_verify(''));
    }

    #[Test]
    public function csrfVerifyAcceptsCurrentToken(): void
    {
        $_SESSION['pn_csrf_token'] = '';
        $token = pn_csrf_token();

        $this->assertTrue(pn_csrf_verify($token));
    }

    #[Test]
    public function csrfVerifyRejectsInvalidToken(): void
    {
        $_SESSION['pn_csrf_token'] = '';
        pn_csrf_token();

        $this->assertFalse(pn_csrf_verify('ungültig'));
        $this->assertFalse(pn_csrf_verify(str_repeat('0', 64)));
    }

    #[Test]
    public function csrfVerifyRejectsNonStringInput(): void
    {
        $_SESSION['pn_csrf_token'] = str_repeat('a', 64);
        $this->assertFalse(pn_csrf_verify(12345));
        $this->assertFalse(pn_csrf_verify(['token' => 'x']));
    }

    #[Test]
    public function csrfVerifyFailsWhenNoStoredTokenExists(): void
    {
        $_SESSION = [];
        $this->assertFalse(pn_csrf_verify('anything'));
    }
}
