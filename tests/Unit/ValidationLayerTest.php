<?php
declare(strict_types=1);

namespace PowerNews\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

// Include the validation layer
require_once __DIR__ . '/../../pninc/validation.inc.php';

/**
 * Tests for the validation layer functions
 */
class ValidationLayerTest extends TestCase
{
    // === pn_validate_id() Tests ===

    #[Test]
    public function validateIdReturnsPositiveInteger(): void
    {
        $this->assertEquals(123, pn_validate_id(123));
        $this->assertEquals(123, pn_validate_id('123'));
        $this->assertEquals(1, pn_validate_id(1));
    }

    #[Test]
    public function validateIdReturnsZeroForInvalid(): void
    {
        $this->assertEquals(0, pn_validate_id(0));
        $this->assertEquals(0, pn_validate_id(-1));
        $this->assertEquals(0, pn_validate_id(''));
        $this->assertEquals(0, pn_validate_id(null));
        $this->assertEquals(0, pn_validate_id('abc'));
        $this->assertEquals(0, pn_validate_id('0'));
    }

    #[Test]
    public function validateIdHandlesMixedInput(): void
    {
        $this->assertEquals(42, pn_validate_id('42'));
        $this->assertEquals(0, pn_validate_id('42abc')); // filter_var returns false
    }

    // === pn_validate_string() Tests ===

    #[Test]
    public function validateStringTrimsWhitespace(): void
    {
        $this->assertEquals('test', pn_validate_string('  test  '));
        $this->assertEquals('hello world', pn_validate_string('  hello world  '));
    }

    #[Test]
    public function validateStringRespectsMaxLength(): void
    {
        $this->assertEquals('abc', pn_validate_string('abcdef', 3));
        $this->assertEquals('test', pn_validate_string('test', 10));
    }

    #[Test]
    public function validateStringHandlesNull(): void
    {
        $this->assertEquals('', pn_validate_string(null));
    }

    #[Test]
    public function validateStringPreservesUnicode(): void
    {
        $this->assertEquals('äöü', pn_validate_string('äöü'));
        $this->assertEquals('日本語', pn_validate_string('日本語'));
    }

    // === pn_validate_whitelist() Tests ===

    #[Test]
    public function validateWhitelistAcceptsValidValues(): void
    {
        $allowed = ['red', 'green', 'blue'];
        $this->assertEquals('red', pn_validate_whitelist('red', $allowed, 'default'));
        $this->assertEquals('green', pn_validate_whitelist('green', $allowed, 'default'));
    }

    #[Test]
    public function validateWhitelistReturnsDefaultForInvalid(): void
    {
        $allowed = ['red', 'green', 'blue'];
        $this->assertEquals('default', pn_validate_whitelist('yellow', $allowed, 'default'));
        $this->assertEquals('default', pn_validate_whitelist('', $allowed, 'default'));
        $this->assertEquals('default', pn_validate_whitelist(null, $allowed, 'default'));
    }

    // === pn_validate_email() Tests ===

    #[Test]
    public function validateEmailAcceptsValid(): void
    {
        $this->assertEquals('test@example.com', pn_validate_email('test@example.com'));
        $this->assertEquals('user.name@domain.org', pn_validate_email('user.name@domain.org'));
    }

    #[Test]
    public function validateEmailRejectsInvalid(): void
    {
        $this->assertEquals('', pn_validate_email('invalid'));
        $this->assertEquals('', pn_validate_email('test@'));
        $this->assertEquals('', pn_validate_email('@example.com'));
        $this->assertEquals('', pn_validate_email(''));
        $this->assertEquals('', pn_validate_email(null));
    }

    #[Test]
    public function validateEmailTrimsWhitespace(): void
    {
        $this->assertEquals('test@example.com', pn_validate_email('  test@example.com  '));
    }

    // === pn_validate_url() Tests ===

    #[Test]
    public function validateUrlAcceptsHttpAndHttps(): void
    {
        $this->assertEquals('http://example.com', pn_validate_url('http://example.com'));
        $this->assertEquals('https://example.com', pn_validate_url('https://example.com'));
    }

    #[Test]
    public function validateUrlRejectsOtherSchemes(): void
    {
        $this->assertEquals('', pn_validate_url('ftp://example.com'));
        $this->assertEquals('', pn_validate_url('javascript:alert(1)'));
        $this->assertEquals('', pn_validate_url('file:///etc/passwd'));
    }

    #[Test]
    public function validateUrlRejectsInvalid(): void
    {
        $this->assertEquals('', pn_validate_url('not a url'));
        $this->assertEquals('', pn_validate_url(''));
        $this->assertEquals('', pn_validate_url(null));
    }

    // === pn_validate_yesno() Tests ===

    #[Test]
    public function validateYesnoAcceptsYesNo(): void
    {
        $this->assertEquals('YES', pn_validate_yesno('YES'));
        $this->assertEquals('NO', pn_validate_yesno('NO'));
    }

    #[Test]
    public function validateYesnoReturnsDefault(): void
    {
        $this->assertEquals('NO', pn_validate_yesno('maybe'));
        $this->assertEquals('NO', pn_validate_yesno(''));
        $this->assertEquals('YES', pn_validate_yesno('', 'YES'));
    }

    // === pn_validate_status() Tests ===

    #[Test]
    public function validateStatusAcceptsValidStatuses(): void
    {
        $this->assertEquals('Activated', pn_validate_status('Activated'));
        $this->assertEquals('Deactivated', pn_validate_status('Deactivated'));
        $this->assertEquals('Unchecked', pn_validate_status('Unchecked'));
    }

    #[Test]
    public function validateStatusReturnsDefault(): void
    {
        $this->assertEquals('Unchecked', pn_validate_status('Invalid'));
        $this->assertEquals('Activated', pn_validate_status('Invalid', 'Activated'));
    }

    // === pn_validate_int_range() Tests ===

    #[Test]
    public function validateIntRangeReturnsValueInRange(): void
    {
        $this->assertEquals(5, pn_validate_int_range(5, 1, 10, 1));
        $this->assertEquals(1, pn_validate_int_range(1, 1, 10, 5));
        $this->assertEquals(10, pn_validate_int_range(10, 1, 10, 5));
    }

    #[Test]
    public function validateIntRangeClampsToMinMax(): void
    {
        $this->assertEquals(1, pn_validate_int_range(0, 1, 10, 5));
        $this->assertEquals(10, pn_validate_int_range(100, 1, 10, 5));
    }

    #[Test]
    public function validateIntRangeReturnsDefaultForInvalid(): void
    {
        $this->assertEquals(5, pn_validate_int_range('abc', 1, 10, 5));
        $this->assertEquals(5, pn_validate_int_range(null, 1, 10, 5));
    }

    // === pn_validate_date() Tests ===

    #[Test]
    public function validateDateReturnsValidComponents(): void
    {
        $result = pn_validate_date(15, 6, 2024);
        $this->assertEquals(15, $result['day']);
        $this->assertEquals(6, $result['month']);
        $this->assertEquals(2024, $result['year']);
    }

    #[Test]
    public function validateDateClampsInvalidValues(): void
    {
        $result = pn_validate_date(0, 13, 1900);
        $this->assertEquals(1, $result['day']);
        $this->assertEquals(12, $result['month']);
        $this->assertEquals(1970, $result['year']);
    }

    // === pn_validate_time() Tests ===

    #[Test]
    public function validateTimeReturnsValidComponents(): void
    {
        $result = pn_validate_time(14, 30);
        $this->assertEquals(14, $result['hour']);
        $this->assertEquals(30, $result['minute']);
    }

    #[Test]
    public function validateTimeClampsInvalidValues(): void
    {
        $result = pn_validate_time(25, 70);
        $this->assertEquals(23, $result['hour']);
        $this->assertEquals(59, $result['minute']);
    }
}
