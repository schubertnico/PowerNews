<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

class AdminConfigIntegrationTest extends DatabaseTestCase
{
    private \configuration $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDatabase();
        $this->config = new \configuration();
    }

    // ── editconfig ──

    #[Test]
    public function editconfig_returns_empty_string_on_valid_config(): void
    {
        $configData = new \ConfigData(
            commentwriting: 'Registered',
            newssending: 'Registered',
            url: 'http://example.com',
            email: 'admin@example.com',
        );

        $result = $this->config->editconfig($configData);

        $this->assertSame('', $result);
    }

    #[Test]
    public function editconfig_returns_wrong_email_when_email_empty(): void
    {
        $configData = new \ConfigData(
            url: 'http://example.com',
            email: '',
        );

        $result = $this->config->editconfig($configData);

        $this->assertSame(L_CONF_WRONGEMAIL, $result);
    }

    #[Test]
    public function editconfig_returns_wrong_url_when_url_empty_but_email_valid(): void
    {
        $configData = new \ConfigData(
            url: '',
            email: 'admin@example.com',
        );

        $result = $this->config->editconfig($configData);

        $this->assertSame(L_CONF_WRONGURL, $result);
    }

    #[Test]
    public function editconfig_returns_wrong_email_when_both_empty(): void
    {
        $configData = new \ConfigData(
            url: '',
            email: '',
        );

        $result = $this->config->editconfig($configData);

        $this->assertSame(L_CONF_WRONGEMAIL, $result);
    }

    #[Test]
    public function editconfig_persists_config_to_database(): void
    {
        global $pn_handler, $pn_config;

        $configData = new \ConfigData(
            categories: 'YES',
            comments: 'NO',
            commentwriting: 'Registered',
            moretext: 'YES',
            newssending: 'Registered',
            url: 'http://newsite.com',
            email: 'new@example.com',
            headlines: 15,
            news: 20,
        );

        $result = $this->config->editconfig($configData);
        $this->assertSame('', $result);

        // Verify the data was written to the database
        $dbResult = mysqli_query($pn_handler, 'SELECT * FROM ' . $pn_config['configtable']);
        $row = mysqli_fetch_assoc($dbResult);

        $this->assertSame('YES', $row['categories']);
        $this->assertSame('NO', $row['comments']);
        $this->assertSame('YES', $row['moretext']);
        $this->assertSame('http://newsite.com', $row['url']);
        $this->assertSame('new@example.com', $row['email']);
        $this->assertSame('15', (string) $row['headlines']);
        $this->assertSame('20', (string) $row['news']);
    }

    #[Test]
    public function editconfig_preserves_all_fields(): void
    {
        global $pn_handler, $pn_config;

        $configData = new \ConfigData(
            categories: 'NO',
            categorypics: 'YES',
            comments: 'YES',
            commentwriting: 'Registered',
            moretext: 'NO',
            sendnews: 'YES',
            newssending: 'Registered',
            smilies: 'Comments',
            bbcode: 'Comments/News',
            html: 'News',
            dateformat: 'Y-m-d',
            timeformat: 'H:i:s',
            template: 1,
            url: 'http://test.com',
            email: 'test@test.com',
            headlines: 3,
            news: 7,
            spamprotection: 120,
            relatedlinks: 'YES',
            relatedlinks_num: 5,
        );

        $this->config->editconfig($configData);

        $dbResult = mysqli_query($pn_handler, 'SELECT * FROM ' . $pn_config['configtable']);
        $row = mysqli_fetch_assoc($dbResult);

        $this->assertSame('NO', $row['categories']);
        $this->assertSame('YES', $row['categorypics']);
        $this->assertSame('Registered', $row['commentwriting']);
        $this->assertSame('Y-m-d', $row['dateformat']);
        $this->assertSame('H:i:s', $row['timeformat']);
        $this->assertSame('YES', $row['relatedlinks']);
    }

    // ── listtemplates ──

    #[Test]
    public function listtemplates_outputs_option_elements_with_templates(): void
    {
        $output = $this->captureOutput(fn() => $this->config->listtemplates());

        // The default template (id=1) should be present from the schema seed
        $this->assertStringContainsString('<option', $output);
        $this->assertStringContainsString('value="1"', $output);
    }

    #[Test]
    public function listtemplates_marks_selected_template(): void
    {
        global $pnconfig;

        // pnconfig['template'] is set from the DB seed, typically '1'
        $pnconfig['template'] = '1';

        $output = $this->captureOutput(fn() => $this->config->listtemplates());

        $this->assertStringContainsString('selected', $output);
    }

    #[Test]
    public function listtemplates_outputs_no_templates_message_when_table_empty(): void
    {
        global $pn_handler, $pn_config;

        mysqli_query($pn_handler, 'TRUNCATE TABLE ' . $pn_config['templatetable']);

        $output = $this->captureOutput(fn() => $this->config->listtemplates());

        $this->assertStringContainsString(L_CONF_NOTEMPLATES, $output);
    }
}
