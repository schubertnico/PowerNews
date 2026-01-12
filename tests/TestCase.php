<?php
declare(strict_types=1);

namespace PowerNews\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Base TestCase for PowerNews tests
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Set up superglobals for testing
     */
    protected function setGet(array $data): void
    {
        $_GET = $data;
    }

    protected function setPost(array $data): void
    {
        $_POST = $data;
    }

    protected function setCookie(array $data): void
    {
        $_COOKIE = $data;
    }

    protected function setServer(array $data): void
    {
        $_SERVER = array_merge($_SERVER, $data);
    }

    protected function clearSuperglobals(): void
    {
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_FILES = [];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->clearSuperglobals();
    }

    protected function tearDown(): void
    {
        $this->clearSuperglobals();
        parent::tearDown();
    }

    /**
     * Assert that a string is a valid email format
     */
    protected function assertValidEmail(string $email): void
    {
        $this->assertMatchesRegularExpression(
            '/^[_a-zA-Z0-9.-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            $email,
            "Expected valid email format, got: $email"
        );
    }

    /**
     * Assert that a string is NOT a valid email format
     */
    protected function assertInvalidEmail(string $email): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/^[_a-zA-Z0-9.-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            $email,
            "Expected invalid email format, got: $email"
        );
    }
}
