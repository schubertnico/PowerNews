<?php
declare(strict_types=1);

namespace PowerNews\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for password hashing and verification functions
 */
class PasswordTest extends TestCase
{
    #[Test]
    public function hashPasswordCreatesBcryptHash(): void
    {
        $password = 'test123';
        $hash = pn_hash_password($password);

        $this->assertStringStartsWith('$2y$', $hash);
        $this->assertGreaterThan(50, strlen($hash));
    }

    #[Test]
    public function verifyPasswordWithBcryptHash(): void
    {
        $password = 'securePassword123';
        $hash = pn_hash_password($password);

        $this->assertTrue(pn_verify_password($password, $hash));
        $this->assertFalse(pn_verify_password('wrongPassword', $hash));
    }

    #[Test]
    public function verifyPasswordWithLegacyBase64Hash(): void
    {
        $password = 'oldPassword';
        $legacyHash = base64_encode($password);

        $this->assertTrue(pn_verify_password($password, $legacyHash));
        $this->assertFalse(pn_verify_password('wrongPassword', $legacyHash));
    }

    #[Test]
    public function isLegacyPasswordDetectsBcrypt(): void
    {
        $bcryptHash = '$2y$12$abcdefghijklmnopqrstuv';

        $this->assertFalse(pn_is_legacy_password($bcryptHash));
    }

    #[Test]
    public function isLegacyPasswordDetectsBase64(): void
    {
        $base64Hash = base64_encode('password');

        $this->assertTrue(pn_is_legacy_password($base64Hash));
    }

    #[Test]
    public function isLegacyPasswordDetectsArgon2(): void
    {
        $argonHash = '$argon2id$v=19$m=65536,t=4,p=1$...';

        $this->assertFalse(pn_is_legacy_password($argonHash));
    }

    #[Test]
    public function adminPasswordFunctionsWork(): void
    {
        $password = 'adminPass123';
        $hash = pnadmin_hash_password($password);

        $this->assertStringStartsWith('$2y$', $hash);
        $this->assertTrue(pnadmin_verify_password($password, $hash));
        $this->assertFalse(pnadmin_verify_password('wrong', $hash));
    }

    #[Test]
    public function differentPasswordsProduceDifferentHashes(): void
    {
        $hash1 = pn_hash_password('password1');
        $hash2 = pn_hash_password('password2');

        $this->assertNotEquals($hash1, $hash2);
    }

    #[Test]
    public function samePasswordProducesDifferentHashes(): void
    {
        // Due to salt, same password should produce different hashes
        $hash1 = pn_hash_password('samePassword');
        $hash2 = pn_hash_password('samePassword');

        $this->assertNotEquals($hash1, $hash2);

        // But both should verify correctly
        $this->assertTrue(pn_verify_password('samePassword', $hash1));
        $this->assertTrue(pn_verify_password('samePassword', $hash2));
    }

    #[Test]
    #[DataProvider('specialCharacterPasswordsProvider')]
    public function passwordsWithSpecialCharactersWork(string $password): void
    {
        $hash = pn_hash_password($password);

        $this->assertTrue(pn_verify_password($password, $hash));
    }

    public static function specialCharacterPasswordsProvider(): array
    {
        return [
            'with spaces' => ['pass word with spaces'],
            'with umlauts' => ['Passwört123'],
            'with symbols' => ['P@$$w0rd!#$%'],
            'with unicode' => ['密码测试'],
            'empty string' => [''],
            'very long' => [str_repeat('a', 100)],
        ];
    }
}
