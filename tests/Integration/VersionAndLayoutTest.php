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
        $files = array_merge(glob(__DIR__ . '/../../pninc/*.php') ?: [], glob(__DIR__ . '/../../pninc/lang/*.php') ?: [], glob(__DIR__ . '/../../pnadmin/*.php') ?: [], glob(__DIR__ . '/../../pnadmin/lang/*.php') ?: []);

        foreach ($files as $file) {
            $this->assertStringNotContainsString('GNU General Public License', (string) file_get_contents($file), basename($file));
        }
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
