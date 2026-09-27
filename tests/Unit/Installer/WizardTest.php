<?php

declare(strict_types=1);

namespace PowerNews\Tests\Unit\Installer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PowerNews\Installer\Wizard;

require_once __DIR__ . '/../../../pninc/installer/autoload.php';

/**
 * Fortschritt des Installers in der Session.
 */
final class WizardTest extends TestCase
{
    private const array DB = ['host' => 'sql.example.org', 'port' => 3306, 'user' => 'news_user', 'password' => 'Lichtblick-DB26', 'database' => 'news_db'];

    private const array WEBSITE = ['url' => 'http://localhost:8229', 'email' => 'news@example.org', 'language' => 'german-du'];

    private static function completeWizard(): Wizard
    {
        $wizard = new Wizard();
        $wizard->completeRequirements();
        $wizard->storeDatabase(self::DB, 'MariaDB 10.11.15');
        $wizard->storeWebsite(self::WEBSITE);
        $wizard->storeAdmin('admin', 'admin@example.org', password_hash('sicher-genug', PASSWORD_DEFAULT));

        return $wizard;
    }

    #[Test]
    public function newWizardStartsAtStepOne(): void
    {
        $wizard = Wizard::fromSession(null);

        $this->assertSame(0, $wizard->completed());
        $this->assertSame(1, $wizard->allowedStep(5), 'Spätere Schritte sind ohne Daten nicht erreichbar');
        $this->assertSame(1, $wizard->allowedStep(-3));
        $this->assertTrue($wizard->canEnter(1));
        $this->assertFalse($wizard->canEnter(2));
    }

    #[Test]
    public function stepsBecomeReachableOneAfterAnother(): void
    {
        $wizard = new Wizard();
        $wizard->completeRequirements();
        $this->assertSame(2, $wizard->allowedStep(5));

        $wizard->storeDatabase(self::DB, 'MariaDB 10.11.15');
        $this->assertSame(3, $wizard->allowedStep(5));

        $wizard->storeWebsite(self::WEBSITE);
        $this->assertSame(4, $wizard->allowedStep(5));

        $wizard->storeAdmin('admin', 'admin@example.org', 'hash');
        $this->assertSame(5, $wizard->allowedStep(9));
        $this->assertSame(Wizard::STEP_ADMIN, $wizard->completed());
    }

    #[Test]
    public function sessionRoundTripKeepsAllData(): void
    {
        $wizard = self::completeWizard();
        $wizard->setNotice('success', 'Verbindung hergestellt');

        $restored = Wizard::fromSession($wizard->toSession());

        $this->assertSame(self::DB, $restored->database());
        $this->assertSame('MariaDB 10.11.15', $restored->serverLabel());
        $this->assertSame(self::WEBSITE, $restored->website());
        $this->assertSame('admin', $restored->admin()['nickname'] ?? null);
        $this->assertSame(['type' => 'success', 'message' => 'Verbindung hergestellt'], $restored->takeNotice());
        $this->assertNull($restored->takeNotice(), 'Meldungen erscheinen nur einmal');
    }

    #[Test]
    public function adminPasswordIsOnlyStoredAsHash(): void
    {
        $session = self::completeWizard()->toSession();

        $this->assertStringNotContainsString('sicher-genug', serialize($session));
        $this->assertTrue(password_verify('sicher-genug', $session['admin']['password_hash'] ?? ''));
    }

    /**
     * @return iterable<string, array{mixed, int}>
     */
    public static function manipulatedSessions(): iterable
    {
        yield 'kein Array' => ['kaputt', Wizard::STEP_REQUIREMENTS];
        yield 'completed zu groß, Daten fehlen' => [['completed' => 99], Wizard::STEP_DATABASE];
        yield 'Port als Text' => [['completed' => 5, 'database' => ['port' => '3306'] + self::DB], Wizard::STEP_DATABASE];
        yield 'Website unvollständig' => [['completed' => 5, 'database' => self::DB, 'website' => ['url' => 'x']], Wizard::STEP_WEBSITE];
        yield 'Abschluss ohne Typen' => [['completed' => 5, 'done' => ['config_written' => 'ja']], Wizard::STEP_DATABASE];
    }

    #[Test]
    #[DataProvider('manipulatedSessions')]
    public function manipulatedSessionDataLeadsBackToAnEarlierStep(mixed $data, int $expectedStep): void
    {
        $wizard = Wizard::fromSession($data);

        $this->assertSame($expectedStep, $wizard->allowedStep(Wizard::STEP_FINISH));
        $this->assertNull($wizard->done());
    }

    #[Test]
    public function finishForgetsCredentialsButKeepsTheDownloadIfNeeded(): void
    {
        $wizard = self::completeWizard();
        $wizard->finish(false, "<?php\nreturn ['db' => []];\n", 'logs/install.lock');

        $done = $wizard->done();
        $this->assertNotNull($done);
        $this->assertFalse($done['config_written']);
        $this->assertStringContainsString('return', $done['config_source']);
        $this->assertSame('logs/install.lock', $done['lock_file']);
        $this->assertSame('admin', $done['admin_nickname']);
        $this->assertSame('http://localhost:8229', $done['site_url']);
        $this->assertNull($wizard->database());
        $this->assertNull($wizard->admin());
        $this->assertStringNotContainsString('Lichtblick-DB26', serialize(array_diff_key($wizard->toSession(), ['done' => 1])));

        $wizard->forgetConfigSource();
        $this->assertSame('', $wizard->done()['config_source'] ?? null);

        $restored = Wizard::fromSession($wizard->toSession());
        $this->assertSame('logs/install.lock', $restored->done()['lock_file'] ?? null);
    }

    #[Test]
    public function writtenConfigIsNotKeptInTheSession(): void
    {
        $wizard = self::completeWizard();
        $wizard->finish(true, 'geheim', 'pninc/install.lock');

        $this->assertSame('', $wizard->done()['config_source'] ?? null);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function requests(): iterable
    {
        yield 'Web-Root' => [['HTTP_HOST' => 'localhost:8229', 'SCRIPT_NAME' => '/install.php'], 'http://localhost:8229'];
        yield 'Unterverzeichnis mit HTTPS' => [['HTTP_HOST' => 'www.example.org', 'SCRIPT_NAME' => '/news/install.php', 'HTTPS' => 'on'], 'https://www.example.org/news'];
        yield 'manipulierter Host' => [['HTTP_HOST' => 'evil.example"><script>', 'SCRIPT_NAME' => '/install.php'], ''];
        yield 'kein Host' => [['SCRIPT_NAME' => '/install.php'], ''];
        yield 'Pfad mit Sonderzeichen' => [['HTTP_HOST' => 'example.org', 'SCRIPT_NAME' => '/a b/install.php'], 'http://example.org'];
    }

    /**
     * @param array<string, mixed> $server
     */
    #[Test]
    #[DataProvider('requests')]
    public function siteUrlIsSuggestedFromTheRequest(array $server, string $expected): void
    {
        $this->assertSame($expected, Wizard::suggestSiteUrl($server));
    }

    #[Test]
    public function senderIsSuggestedFromTheDomain(): void
    {
        $this->assertSame('noreply@example.org', Wizard::suggestSender('https://www.example.org/news'));
        $this->assertSame('noreply@news.example.org', Wizard::suggestSender('http://news.example.org:8080'));
        $this->assertSame('', Wizard::suggestSender('http://localhost:8229'), 'localhost ergibt keine gültige Adresse');
        $this->assertSame('', Wizard::suggestSender(''));
    }
}
