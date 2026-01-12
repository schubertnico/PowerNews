<?php
declare(strict_types=1);
/**
 * PHPUnit Bootstrap for PowerNews Tests
 */

// Autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Define test constants
define('POWERNEWS_TEST', true);

// Load real source files for unit tests (without database connection)
// Note: Escape and validation layer tests include their own source files
// to avoid conflicts with other tests

/**
 * Check if password is legacy base64 encoded
 */
function pn_is_legacy_password(string $hash): bool {
    return !str_starts_with($hash, '$2y$') && !str_starts_with($hash, '$2a$') && !str_starts_with($hash, '$argon');
}

/**
 * Hash password using bcrypt
 */
function pn_hash_password(string $password): string {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password (supports legacy base64 and bcrypt)
 * Note: $userId is for legacy auto-upgrade (not implemented in tests)
 */
function pn_verify_password(string $password, string $storedHash, ?int $userId = null): bool {
    if (pn_is_legacy_password($storedHash)) {
        return base64_encode($password) === $storedHash;
    }
    return password_verify($password, $storedHash);
}

/**
 * Admin password functions (same as frontend)
 */
function pnadmin_is_legacy_password(string $hash): bool {
    return pn_is_legacy_password($hash);
}

function pnadmin_hash_password(string $password): string {
    return pn_hash_password($password);
}

function pnadmin_verify_password(string $password, string $storedHash, ?int $userId = null): bool {
    return pn_verify_password($password, $storedHash, $userId);
}
