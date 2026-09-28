<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

/**
 * Regressionstests für B12 (Version aus einer Konstante), B17 (Navbar), B19 (Lizenz) und
 * B49 (Testhinweis-Box).
 */
class VersionAndLayoutTest extends DatabaseTestCase
{
    private function source(string $file): string
    {
        return (string) file_get_contents(__DIR__ . '/../../' . $file);
    }

    #[Test]
    public function version_and_years_come_from_one_place(): void
    {
        $this->assertMatchesRegularExpression('/^\d+\.\d+(\.\d+)?$/', PN_VERSION);
        $this->assertMatchesRegularExpression('/^2001-\d{4}$/', PN_COPYRIGHT_YEARS);

        $this->assertStringContainsString('<title>PowerNews <?php echo PN_VERSION; ?></title>', $this->source('header.inc.php'));
        $this->assertStringContainsString('PN_VERSION', $this->source('footer.inc.php'));
        $this->assertStringContainsString('$psdesignversion = PN_VERSION;', $this->source('pnadmin/index.php'));
        $this->assertStringNotContainsString("\$pn_config['version']", $this->source('pnadmin/index.php'));
        $this->assertStringNotContainsString('3.0', $this->source('header.inc.php'));
        $this->assertStringNotContainsString('v3.0', $this->source('README.html'));
    }

    #[Test]
    public function license_page_shows_the_mit_license(): void
    {
        $this->assertFileDoesNotExist(__DIR__ . '/../../pnadmin/gnulicense.txt');

        $output = $this->renderAdminPage('other_license.inc.php', ['page' => 'other', 'subpage' => 'license']);

        $this->assertStringContainsString('name="license"', $output);
        $this->assertStringContainsString('MIT License', $output);
        $this->assertStringNotContainsString('GNU', $output);
        $this->assertStringNotContainsString('General Public License', \L_OTHER_LICENSE_DESC);
    }

    #[Test]
    public function no_gpl_headers_remain_in_application_files(): void
    {
        $root = __DIR__ . '/../..';
        $files = array_merge(
            glob($root . '/*.php') ?: [],
            glob($root . '/pninc/*.php') ?: [],
            glob($root . '/pninc/lang/*.php') ?: [],
            glob($root . '/pninc/installer/*.php') ?: [],
            glob($root . '/pninc/installer/templates/*.php') ?: [],
            glob($root . '/pnadmin/*.php') ?: [],
            glob($root . '/pnadmin/*.css') ?: [],
            glob($root . '/pnadmin/lang/*.php') ?: [],
        );

        $this->assertContains(realpath($root . '/install.php'), array_map('realpath', $files));
        foreach ($files as $file) {
            $content = (string) file_get_contents($file);
            $this->assertStringNotContainsString('GNU General Public License', $content, basename($file));
            $this->assertStringNotContainsString('Vorraussetzung', $content, basename($file));
        }
        $this->assertStringContainsString('MIT License', $this->source('install.php'));
        $this->assertStringContainsString('MIT License', $this->source('update.php'));
        $this->assertStringContainsString('MIT License', $this->source('pnadmin/poweradmin.css'));
    }

    #[Test]
    public function documents_and_update_name_the_version_from_the_constant(): void
    {
        $this->assertStringStartsWith('# PowerNews ' . PN_VERSION . "\n", $this->source('README.md'));
        $this->assertStringStartsWith('# PowerNews ' . PN_VERSION . ' – Installation und Update', $this->source('INSTALLATION.md'));
        $this->assertMatchesRegularExpression('/^## ' . preg_quote(PN_VERSION, '/') . ' – \d{2}\.\d{2}\.\d{4}$/m', $this->source('CHANGELOG.md'));

        $readme = $this->source('README.html');
        $this->assertStringContainsString('<title>PowerNews ' . PN_VERSION . ' – Dokumentation</title>', $readme);
        $this->assertStringContainsString('<h1>PowerNews ' . PN_VERSION . '</h1>', $readme);
        // docs/ ist nicht im Release-Archiv, LICENSE sperrt die .htaccess – keine toten Verweise.
        $this->assertStringNotContainsString('docs/', $readme);
        $this->assertStringNotContainsString('href="LICENSE"', $readme);
        $this->assertStringContainsString('https://www.powerscripts.org/projects-1.html', $readme);

        $this->assertStringContainsString("\$pn_config['version'] = PN_VERSION;", $this->source('pninc/config.inc.php'));
        $this->assertStringContainsString("'version' => PN_VERSION,", $this->source('update.php'));
        $this->assertStringContainsString('PN_VERSION', $this->source('pninc/installer/templates/layout.php'));
    }

    #[Test]
    public function test_notice_box_is_gone_and_navbar_text_stays_light(): void
    {
        $this->assertStringNotContainsString('Bitte beachten', $this->source('header.inc.php'));
        $this->assertStringNotContainsString('nur zu Testzwecken', $this->source('header.inc.php'));

        $index = $this->source('pnadmin/index.php');
        $this->assertMatchesRegularExpression('/\.navbar \.navbar-text,\s*body\.pn-admin-body \.navbar \.small \{\s*color: #f8f9fa !important;/', $index);
        $this->assertStringContainsString('<span class="d-none d-xxl-inline">', $index);
    }
}
