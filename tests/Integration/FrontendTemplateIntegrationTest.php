<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

class FrontendTemplateIntegrationTest extends DatabaseTestCase
{
    private \pn_template $template;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDatabase();
        $this->template = new \pn_template();
    }

    // ── message ──

    #[Test]
    public function message_returns_true_with_valid_template(): void
    {
        $result = $this->captureOutput(function () {
            $ret = $this->template->message('Something happened', 'http://example.com');
            $this->assertTrue($ret);
        });

        $this->assertStringContainsString('Something happened', $result);
        $this->assertStringContainsString('http://example.com', $result);
    }

    #[Test]
    public function message_output_replaces_message_and_link_placeholders(): void
    {
        $output = $this->captureOutput(function () {
            $this->template->message('Test alert text', 'http://go-back.test');
        });

        $this->assertStringContainsString('Test alert text', $output);
        $this->assertStringContainsString('http://go-back.test', $output);
        // Placeholders should be gone
        $this->assertStringNotContainsString('{MESSAGE}', $output);
        $this->assertStringNotContainsString('{LINK}', $output);
    }

    // ── headline ──

    #[Test]
    public function headline_returns_true_and_outputs_title(): void
    {
        $time = mktime(14, 30, 0, 6, 15, 2024);

        $output = $this->captureOutput(function () use ($time) {
            $ret = $this->template->headline(1, $time, 'General', 'Breaking News');
            $this->assertTrue($ret);
        });

        $this->assertStringContainsString('Breaking News', $output);
    }

    #[Test]
    public function headline_outputs_formatted_date(): void
    {
        global $pnconfig;

        $time = mktime(10, 0, 0, 3, 25, 2024);

        $output = $this->captureOutput(function () use ($time) {
            $this->template->headline(42, $time, 'Tech', 'Headline Title');
        });

        $datetime = new \DateTime();
        $datetime->setTimestamp($time);
        $expectedDate = $datetime->format($pnconfig['dateformat']);

        $this->assertStringContainsString($expectedDate, $output);
    }

    #[Test]
    public function headline_replaces_category_name_from_array(): void
    {
        $time = time();
        $category = ['id' => 5, 'name' => 'Sports', 'pic' => '<img src="test.gif">'];

        $output = $this->captureOutput(function () use ($time, $category) {
            $this->template->headline(10, $time, $category, 'Sports Headline');
        });

        $this->assertStringContainsString('Sports', $output);
    }

    // ── news ──

    #[Test]
    public function news_returns_true_and_contains_title_and_text(): void
    {
        $time = time();

        $output = $this->captureOutput(function () use ($time) {
            $ret = $this->template->news(1, 'AuthorName', $time, 'Category', 'News Title', 'News body text', 5, 'NO');
            $this->assertTrue($ret);
        });

        $this->assertStringContainsString('News Title', $output);
        $this->assertStringContainsString('News body text', $output);
    }

    #[Test]
    public function news_with_details_yes_and_moretext_shows_moretext(): void
    {
        global $pnconfig;
        $origMoretext = $pnconfig['moretext'];
        $pnconfig['moretext'] = 'YES';

        $time = time();

        $output = $this->captureOutput(function () use ($time) {
            $this->template->news(1, 'Author', $time, 'Cat', 'Title', 'Short text', 0, 'YES', 'Extended content here');
        });

        $this->assertStringContainsString('Extended content here', $output);
        $this->assertStringContainsString('Short text', $output);

        $pnconfig['moretext'] = $origMoretext;
    }

    #[Test]
    public function news_with_details_no_and_moretext_shows_more_link(): void
    {
        global $pnconfig;
        $origMoretext = $pnconfig['moretext'];
        $pnconfig['moretext'] = 'YES';

        $time = time();

        $output = $this->captureOutput(function () use ($time) {
            $this->template->news(99, 'Author', $time, 'Cat', 'Title', 'Short text', 0, 'NO', 'Extra content');
        });

        $this->assertStringContainsString(L_NEWS_MORE, $output);
        $this->assertStringContainsString('newsid=99', $output);

        $pnconfig['moretext'] = $origMoretext;
    }

    #[Test]
    public function news_contains_author_and_comment_count(): void
    {
        $time = time();

        $output = $this->captureOutput(function () use ($time) {
            $this->template->news(1, 'JohnDoe', $time, 'Tech', 'Title', 'Text', 42, 'NO');
        });

        $this->assertStringContainsString('JohnDoe', $output);
        $this->assertStringContainsString('42', $output);
    }

    // ── comment ──

    #[Test]
    public function comment_guest_author_shows_guest_label(): void
    {
        $time = time();
        $author = ['id' => 0, 'nickname' => L_NEWS_GUEST];

        $output = $this->captureOutput(function () use ($time, $author) {
            $ret = $this->template->comment(1, 10, $author, $time, 'A guest comment');
            $this->assertTrue($ret);
        });

        $this->assertStringContainsString(L_NEWS_GUEST, $output);
        $this->assertStringContainsString('A guest comment', $output);
    }

    #[Test]
    public function comment_registered_author_shows_nickname(): void
    {
        $time = time();
        $author = ['id' => 5, 'nickname' => 'RegularUser', 'showemail' => 'NO', 'email' => 'user@test.com'];

        $output = $this->captureOutput(function () use ($time, $author) {
            $this->template->comment(2, 10, $author, $time, 'Registered user comment');
        });

        $this->assertStringContainsString('RegularUser', $output);
    }

    #[Test]
    public function comment_registered_author_with_showemail_shows_email_link(): void
    {
        $time = time();
        $author = ['id' => 5, 'nickname' => 'MailUser', 'showemail' => 'YES', 'email' => 'mail@test.com'];

        $output = $this->captureOutput(function () use ($time, $author) {
            $this->template->comment(3, 10, $author, $time, 'Comment text');
        });

        $this->assertStringContainsString('mailto:mail@test.com', $output);
        $this->assertStringContainsString('MailUser', $output);
    }

    // ── commentform ──

    #[Test]
    public function commentform_replaces_newsid_and_name(): void
    {
        $output = $this->captureOutput(function () {
            $ret = $this->template->commentform('TestUser', 55);
            $this->assertTrue($ret);
        });

        $this->assertStringContainsString('55', $output);
        $this->assertStringContainsString('TestUser', $output);
        $this->assertStringNotContainsString('{NEWSID}', $output);
        $this->assertStringNotContainsString('{NAME}', $output);
    }

    // ── registerform ──

    #[Test]
    public function registerform_returns_true_and_outputs_form(): void
    {
        $output = $this->captureOutput(function () {
            $ret = $this->template->registerform();
            $this->assertTrue($ret);
        });

        $this->assertNotEmpty($output);
        $this->assertStringContainsString('form', $output);
    }

    // ── loginform ──

    #[Test]
    public function loginform_returns_true_and_outputs_form(): void
    {
        $output = $this->captureOutput(function () {
            $ret = $this->template->loginform();
            $this->assertTrue($ret);
        });

        $this->assertNotEmpty($output);
        $this->assertStringContainsString('form', $output);
    }

    // ── senddataform ──

    #[Test]
    public function senddataform_returns_true_and_outputs_form(): void
    {
        $output = $this->captureOutput(function () {
            $ret = $this->template->senddataform();
            $this->assertTrue($ret);
        });

        $this->assertNotEmpty($output);
        $this->assertStringContainsString('form', $output);
    }

    // ── registeremail ──

    #[Test]
    public function registeremail_returns_string_with_placeholders_replaced(): void
    {
        $result = $this->template->registeremail('nick123', 'nick@test.com', 'secret99');

        $this->assertIsString($result);
        $this->assertStringContainsString('nick123', $result);
        $this->assertStringContainsString('nick@test.com', $result);
        $this->assertStringContainsString('secret99', $result);
        $this->assertStringNotContainsString('{NICKNAME}', $result);
        $this->assertStringNotContainsString('{EMAIL}', $result);
        $this->assertStringNotContainsString('{PASSWORD}', $result);
    }

    #[Test]
    public function registeremail_contains_url_from_config(): void
    {
        global $pnconfig;

        $result = $this->template->registeremail('user', 'u@t.com', 'pw');

        $this->assertIsString($result);
        $this->assertStringContainsString($pnconfig['url'], $result);
        $this->assertStringNotContainsString('{URL}', $result);
    }

    // ── dataemail ──

    #[Test]
    public function dataemail_returns_string_with_placeholders_replaced(): void
    {
        $result = $this->template->dataemail('datanick', 'data@test.com', 'datapass');

        $this->assertIsString($result);
        $this->assertStringContainsString('datanick', $result);
        $this->assertStringContainsString('data@test.com', $result);
        $this->assertStringContainsString('datapass', $result);
        $this->assertStringNotContainsString('{NICKNAME}', $result);
        $this->assertStringNotContainsString('{EMAIL}', $result);
        $this->assertStringNotContainsString('{PASSWORD}', $result);
    }

    #[Test]
    public function dataemail_contains_url_from_config(): void
    {
        global $pnconfig;

        $result = $this->template->dataemail('u', 'u@t.com', 'pw');

        $this->assertIsString($result);
        $this->assertStringContainsString($pnconfig['url'], $result);
        $this->assertStringNotContainsString('{URL}', $result);
    }

    // ── profileform ──

    #[Test]
    public function profileform_replaces_user_placeholders(): void
    {
        $user = [
            'nickname' => 'ProfileUser',
            'email' => 'profile@test.com',
            'showemail' => 'YES',
            'realname' => 'Real Name',
            'city' => 'Berlin',
            'age' => '30',
            'homepage' => 'http://example.com',
            'icq' => '12345',
        ];

        $output = $this->captureOutput(function () use ($user) {
            $ret = $this->template->profileform($user);
            $this->assertTrue($ret);
        });

        $this->assertStringContainsString('ProfileUser', $output);
        $this->assertStringContainsString('profile@test.com', $output);
        $this->assertStringNotContainsString('{NICKNAME}', $output);
        $this->assertStringNotContainsString('{EMAIL}', $output);
    }

    #[Test]
    public function profileform_shows_checked_when_showemail_yes(): void
    {
        $user = [
            'nickname' => 'User',
            'email' => 'e@t.com',
            'showemail' => 'YES',
        ];

        $output = $this->captureOutput(function () use ($user) {
            $this->template->profileform($user);
        });

        $this->assertStringContainsString('checked', $output);
    }

    // ── logout ──

    #[Test]
    public function logout_replaces_nickname_placeholder(): void
    {
        $user = ['nickname' => 'LogoutUser', 'email' => 'lo@t.com'];

        $output = $this->captureOutput(function () use ($user) {
            $ret = $this->template->logout($user);
            $this->assertTrue($ret);
        });

        $this->assertStringContainsString('LogoutUser', $output);
        $this->assertStringNotContainsString('{NICKNAME}', $output);
    }

    // ── archive ──

    #[Test]
    public function archive_outputs_form_with_year_and_month_selects(): void
    {
        $pndata = [
            'showyear' => '2024',
            'showmonth' => '6',
            'yearselect' => '<select name="pndata[showyear]"><option value="2024">2024</option></select>',
        ];

        $output = $this->captureOutput(function () use ($pndata) {
            $ret = $this->template->archive($pndata);
            $this->assertTrue($ret);
        });

        $this->assertStringContainsString('select', $output);
        $this->assertStringContainsString('2024', $output);
        $this->assertStringNotContainsString('{SELECTYEAR}', $output);
        $this->assertStringNotContainsString('{SELECTMONTH}', $output);
    }

    // ── usermenu / usermenu2 ──

    #[Test]
    public function usermenu_returns_true_and_outputs_content(): void
    {
        $output = $this->captureOutput(function () {
            $ret = $this->template->usermenu();
            $this->assertTrue($ret);
        });

        $this->assertNotEmpty($output);
    }

    #[Test]
    public function usermenu2_returns_true_and_outputs_content(): void
    {
        $output = $this->captureOutput(function () {
            $ret = $this->template->usermenu2();
            $this->assertTrue($ret);
        });

        $this->assertNotEmpty($output);
    }

    // ── sendnewsform ──

    #[Test]
    public function sendnewsform_replaces_user_and_categoryselect(): void
    {
        $output = $this->captureOutput(function () {
            $ret = $this->template->sendnewsform('TestAuthor', '<select><option>Cat1</option></select>');
            $this->assertTrue($ret);
        });

        $this->assertStringContainsString('TestAuthor', $output);
        $this->assertStringContainsString('Cat1', $output);
        $this->assertStringNotContainsString('{USER}', $output);
        $this->assertStringNotContainsString('{CATEGORYSELECT}', $output);
    }

    // ── relatedlinks ──

    #[Test]
    public function relatedlinks_returns_string_with_placeholders_replaced(): void
    {
        $result = $this->template->relatedlinks('Example Link', 'http://example.com', '_blank');

        $this->assertIsString($result);
        $this->assertStringContainsString('Example Link', $result);
        $this->assertStringContainsString('http://example.com', $result);
        $this->assertStringContainsString('_blank', $result);
        $this->assertStringNotContainsString('{TITLE}', $result);
        $this->assertStringNotContainsString('{URL}', $result);
        $this->assertStringNotContainsString('{TARGET}', $result);
    }

    #[Test]
    public function relatedlinks_escapes_html_in_title(): void
    {
        $result = $this->template->relatedlinks('<script>alert(1)</script>', 'http://safe.com', '_self');

        $this->assertIsString($result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    // ── edge cases ──

    #[Test]
    public function message_with_special_characters_in_text(): void
    {
        $output = $this->captureOutput(function () {
            $ret = $this->template->message('Error: "File" not <found>', 'http://test.com?a=1&b=2');
            $this->assertTrue($ret);
        });

        $this->assertStringContainsString('Error:', $output);
    }

    #[Test]
    public function news_without_moretext_has_empty_more_placeholder(): void
    {
        global $pnconfig;
        $origMoretext = $pnconfig['moretext'];
        $pnconfig['moretext'] = 'NO';

        $time = time();

        $output = $this->captureOutput(function () use ($time) {
            $this->template->news(1, 'Author', $time, 'Cat', 'Title', 'Text', 0, 'NO');
        });

        $this->assertStringNotContainsString('{MORE}', $output);
        $this->assertStringNotContainsString(L_NEWS_MORE, $output);

        $pnconfig['moretext'] = $origMoretext;
    }

    #[Test]
    public function comment_replaces_all_placeholders(): void
    {
        $time = time();
        $author = ['id' => 1, 'nickname' => 'Tester', 'showemail' => 'NO'];

        $output = $this->captureOutput(function () use ($time, $author) {
            $this->template->comment(7, 3, $author, $time, 'Full comment text');
        });

        $this->assertStringNotContainsString('{ID}', $output);
        $this->assertStringNotContainsString('{AUTHOR}', $output);
        $this->assertStringNotContainsString('{DATE}', $output);
        $this->assertStringNotContainsString('{TIME}', $output);
        $this->assertStringNotContainsString('{TEXT}', $output);
    }
}
