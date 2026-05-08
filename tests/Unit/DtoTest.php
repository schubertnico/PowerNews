<?php
declare(strict_types=1);

namespace PowerNews\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DtoTest extends TestCase
{
    private array $originalPost;

    protected function setUp(): void
    {
        $this->originalPost = $_POST;
        $_POST = [];
    }

    protected function tearDown(): void
    {
        $_POST = $this->originalPost;
    }

    // ===========================
    // TemplateData Tests
    // ===========================

    #[Test]
    public function templateDataConstructorSetsAllFields(): void
    {
        $td = new \TemplateData(
            'Title', 'Message', 'Headline', 'News', 'Comment',
            'Usermenu', 'Usermenu2', 'Related', 'CommentForm', 'RegisterForm',
            'LoginForm', 'Logout', 'SendDataForm', 'ProfileForm', 'Archive',
            'SendNewsForm', 'AddEmail', 'EditEmail', 'RegisterEmail', 'DataEmail'
        );

        $this->assertSame('Title', $td->title);
        $this->assertSame('Message', $td->message);
        $this->assertSame('Headline', $td->headline);
        $this->assertSame('News', $td->news);
        $this->assertSame('Comment', $td->comment);
        $this->assertSame('Usermenu', $td->usermenu);
        $this->assertSame('Usermenu2', $td->usermenu2);
        $this->assertSame('Related', $td->relatedlinks);
        $this->assertSame('CommentForm', $td->commentform);
        $this->assertSame('RegisterForm', $td->registerform);
        $this->assertSame('LoginForm', $td->loginform);
        $this->assertSame('Logout', $td->logout);
        $this->assertSame('SendDataForm', $td->senddataform);
        $this->assertSame('ProfileForm', $td->profileform);
        $this->assertSame('Archive', $td->archive);
        $this->assertSame('SendNewsForm', $td->sendnewsform);
        $this->assertSame('AddEmail', $td->addemail);
        $this->assertSame('EditEmail', $td->editemail);
        $this->assertSame('RegisterEmail', $td->registeremail);
        $this->assertSame('DataEmail', $td->dataemail);
    }

    #[Test]
    public function templateDataIsValidReturnsTrueWhenAllFieldsNonEmpty(): void
    {
        $td = new \TemplateData(
            'a', 'b', 'c', 'd', 'e',
            'f', 'g', 'h', 'i', 'j',
            'k', 'l', 'm', 'n', 'o',
            'p', 'q', 'r', 's', 't'
        );

        $this->assertTrue($td->isValid());
    }

    #[Test]
    public function templateDataIsValidReturnsFalseWhenTitleEmpty(): void
    {
        $td = new \TemplateData(
            '', 'b', 'c', 'd', 'e',
            'f', 'g', 'h', 'i', 'j',
            'k', 'l', 'm', 'n', 'o',
            'p', 'q', 'r', 's', 't'
        );

        $this->assertFalse($td->isValid());
    }

    #[Test]
    public function templateDataIsValidReturnsFalseWhenMessageEmpty(): void
    {
        $td = new \TemplateData(
            'a', '', 'c', 'd', 'e',
            'f', 'g', 'h', 'i', 'j',
            'k', 'l', 'm', 'n', 'o',
            'p', 'q', 'r', 's', 't'
        );

        $this->assertFalse($td->isValid());
    }

    #[Test]
    public function templateDataIsValidReturnsFalseWhenLastFieldEmpty(): void
    {
        $td = new \TemplateData(
            'a', 'b', 'c', 'd', 'e',
            'f', 'g', 'h', 'i', 'j',
            'k', 'l', 'm', 'n', 'o',
            'p', 'q', 'r', 's', ''
        );

        $this->assertFalse($td->isValid());
    }

    #[Test]
    public function templateDataIsValidReturnsFalseWhenAllFieldsEmpty(): void
    {
        $td = new \TemplateData(
            '', '', '', '', '',
            '', '', '', '', '',
            '', '', '', '', '',
            '', '', '', '', ''
        );

        $this->assertFalse($td->isValid());
    }

    #[Test]
    public function templateDataIsValidReturnsFalseWhenMiddleFieldEmpty(): void
    {
        $td = new \TemplateData(
            'a', 'b', 'c', 'd', 'e',
            'f', 'g', 'h', 'i', 'j',
            '', 'l', 'm', 'n', 'o',
            'p', 'q', 'r', 's', 't'
        );

        $this->assertFalse($td->isValid());
    }

    #[Test]
    public function templateDataFromPostCreatesInstanceFromPostData(): void
    {
        $_POST = [
            'title' => 'PostTitle',
            'message' => 'PostMessage',
            'headline' => 'PostHeadline',
            'news' => 'PostNews',
            'comment' => 'PostComment',
            'usermenu' => 'PostUsermenu',
            'usermenu2' => 'PostUsermenu2',
            'relatedlinks' => 'PostRelated',
            'commentform' => 'PostCommentForm',
            'registerform' => 'PostRegisterForm',
            'loginform' => 'PostLoginForm',
            'logout' => 'PostLogout',
            'senddataform' => 'PostSendDataForm',
            'profileform' => 'PostProfileForm',
            'archive' => 'PostArchive',
            'sendnewsform' => 'PostSendNewsForm',
            'addemail' => 'PostAddEmail',
            'editemail' => 'PostEditEmail',
            'registeremail' => 'PostRegisterEmail',
            'dataemail' => 'PostDataEmail',
        ];

        $td = \TemplateData::fromPost();

        $this->assertInstanceOf(\TemplateData::class, $td);
        $this->assertSame('PostTitle', $td->title);
        $this->assertSame('PostMessage', $td->message);
        $this->assertSame('PostDataEmail', $td->dataemail);
    }

    #[Test]
    public function templateDataFromPostHandlesMissingPostKeys(): void
    {
        $_POST = [];

        $td = \TemplateData::fromPost();

        $this->assertInstanceOf(\TemplateData::class, $td);
        $this->assertSame('', $td->title);
        $this->assertSame('', $td->message);
        $this->assertFalse($td->isValid());
    }

    #[Test]
    public function templateDataFromPostTruncatesLongValues(): void
    {
        $longString = str_repeat('A', 20000);
        $_POST = [
            'title' => $longString,
            'message' => 'Clean',
            'headline' => 'Clean',
            'news' => 'Clean',
            'comment' => 'Clean',
            'usermenu' => 'Clean',
            'usermenu2' => 'Clean',
            'relatedlinks' => 'Clean',
            'commentform' => 'Clean',
            'registerform' => 'Clean',
            'loginform' => 'Clean',
            'logout' => 'Clean',
            'senddataform' => 'Clean',
            'profileform' => 'Clean',
            'archive' => 'Clean',
            'sendnewsform' => 'Clean',
            'addemail' => 'Clean',
            'editemail' => 'Clean',
            'registeremail' => 'Clean',
            'dataemail' => 'Clean',
        ];

        $td = \TemplateData::fromPost();

        // pn_post_string with limit 10000 should truncate
        $this->assertLessThanOrEqual(10000, strlen($td->title));
    }

    // ===========================
    // PermissionsData Tests
    // ===========================

    #[Test]
    public function permissionsDataConstructorDefaultsAllToNo(): void
    {
        $pd = new \PermissionsData();

        $this->assertSame('NO', $pd->canreadtemplates);
        $this->assertSame('NO', $pd->canwritetemplates);
        $this->assertSame('NO', $pd->canreadconfig);
        $this->assertSame('NO', $pd->canwriteconfig);
        $this->assertSame('NO', $pd->canreadusers);
        $this->assertSame('NO', $pd->canwriteusers);
        $this->assertSame('NO', $pd->canreadpermissions);
        $this->assertSame('NO', $pd->canwritepermissions);
        $this->assertSame('NO', $pd->canreadcategories);
        $this->assertSame('NO', $pd->canwritecategories);
        $this->assertSame('NO', $pd->canreadnews);
        $this->assertSame('NO', $pd->canwritenews);
        $this->assertSame('NO', $pd->canreadcomments);
        $this->assertSame('NO', $pd->canwritecomments);
    }

    #[Test]
    public function permissionsDataConstructorAcceptsCustomValues(): void
    {
        $pd = new \PermissionsData(
            canreadtemplates: 'YES',
            canwritetemplates: 'YES',
            canreadconfig: 'NO',
            canwriteconfig: 'NO',
            canreadusers: 'YES',
            canwriteusers: 'NO',
            canreadpermissions: 'YES',
            canwritepermissions: 'NO',
            canreadcategories: 'YES',
            canwritecategories: 'YES',
            canreadnews: 'YES',
            canwritenews: 'YES',
            canreadcomments: 'NO',
            canwritecomments: 'NO'
        );

        $this->assertSame('YES', $pd->canreadtemplates);
        $this->assertSame('YES', $pd->canwritetemplates);
        $this->assertSame('NO', $pd->canreadconfig);
        $this->assertSame('YES', $pd->canreadusers);
        $this->assertSame('NO', $pd->canwritecomments);
    }

    #[Test]
    public function permissionsDataToArrayReturnsAllFields(): void
    {
        $pd = new \PermissionsData();
        $arr = $pd->toArray();

        $this->assertIsArray($arr);
        $this->assertCount(14, $arr);
        $this->assertArrayHasKey('canreadtemplates', $arr);
        $this->assertArrayHasKey('canwritetemplates', $arr);
        $this->assertArrayHasKey('canreadconfig', $arr);
        $this->assertArrayHasKey('canwriteconfig', $arr);
        $this->assertArrayHasKey('canreadusers', $arr);
        $this->assertArrayHasKey('canwriteusers', $arr);
        $this->assertArrayHasKey('canreadpermissions', $arr);
        $this->assertArrayHasKey('canwritepermissions', $arr);
        $this->assertArrayHasKey('canreadcategories', $arr);
        $this->assertArrayHasKey('canwritecategories', $arr);
        $this->assertArrayHasKey('canreadnews', $arr);
        $this->assertArrayHasKey('canwritenews', $arr);
        $this->assertArrayHasKey('canreadcomments', $arr);
        $this->assertArrayHasKey('canwritecomments', $arr);
    }

    #[Test]
    public function permissionsDataToArrayReturnsCorrectValues(): void
    {
        $pd = new \PermissionsData(
            canreadtemplates: 'YES',
            canwritenews: 'YES'
        );
        $arr = $pd->toArray();

        $this->assertSame('YES', $arr['canreadtemplates']);
        $this->assertSame('YES', $arr['canwritenews']);
        $this->assertSame('NO', $arr['canreadconfig']);
        $this->assertSame('NO', $arr['canwritecomments']);
    }

    #[Test]
    public function permissionsDataToArrayDefaultsAllNo(): void
    {
        $pd = new \PermissionsData();
        $arr = $pd->toArray();

        foreach ($arr as $key => $value) {
            $this->assertSame('NO', $value, "Expected 'NO' for key '$key'");
        }
    }

    #[Test]
    public function permissionsDataFromPostCreatesInstance(): void
    {
        $_POST = [
            'canreadtemplates' => 'YES',
            'canwritetemplates' => 'NO',
            'canreadconfig' => 'YES',
            'canwriteconfig' => 'NO',
            'canreadusers' => 'YES',
            'canwriteusers' => 'YES',
            'canreadpermissions' => 'NO',
            'canwritepermissions' => 'NO',
            'canreadcategories' => 'YES',
            'canwritecategories' => 'NO',
            'canreadnews' => 'YES',
            'canwritenews' => 'YES',
            'canreadcomments' => 'NO',
            'canwritecomments' => 'NO',
        ];

        $pd = \PermissionsData::fromPost();

        $this->assertInstanceOf(\PermissionsData::class, $pd);
        $this->assertSame('YES', $pd->canreadtemplates);
        $this->assertSame('NO', $pd->canwritetemplates);
        $this->assertSame('YES', $pd->canreadconfig);
    }

    #[Test]
    public function permissionsDataFromPostHandlesMissingKeys(): void
    {
        $_POST = [];

        $pd = \PermissionsData::fromPost();

        $this->assertInstanceOf(\PermissionsData::class, $pd);
        // pn_validate_yesno should default to 'NO' for missing values
        $this->assertSame('NO', $pd->canreadtemplates);
        $this->assertSame('NO', $pd->canwritenews);
    }

    #[Test]
    public function permissionsDataFromPostSanitizesInvalidValues(): void
    {
        $_POST = [
            'canreadtemplates' => 'MAYBE',
            'canwritetemplates' => '<script>alert(1)</script>',
            'canreadconfig' => '1',
            'canwriteconfig' => 'true',
            'canreadusers' => '',
            'canwriteusers' => 'yes',
            'canreadpermissions' => 'YES',
            'canwritepermissions' => 'NO',
            'canreadcategories' => 'NO',
            'canwritecategories' => 'NO',
            'canreadnews' => 'NO',
            'canwritenews' => 'NO',
            'canreadcomments' => 'NO',
            'canwritecomments' => 'NO',
        ];

        $pd = \PermissionsData::fromPost();

        // pn_validate_yesno should only allow 'YES' or 'NO'
        $this->assertContains($pd->canreadtemplates, ['YES', 'NO']);
        $this->assertContains($pd->canwritetemplates, ['YES', 'NO']);
        $this->assertSame('YES', $pd->canreadpermissions);
        $this->assertSame('NO', $pd->canwritepermissions);
    }

    #[Test]
    public function permissionsDataConstructorWithAllYes(): void
    {
        $pd = new \PermissionsData(
            'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES',
            'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES'
        );

        $arr = $pd->toArray();
        foreach ($arr as $key => $value) {
            $this->assertSame('YES', $value, "Expected 'YES' for key '$key'");
        }
    }

    // ===========================
    // ConfigData Tests
    // ===========================

    #[Test]
    public function configDataConstructorSetsAllFields(): void
    {
        $cd = new \ConfigData(
            categories: 'YES',
            categorypics: 'YES',
            comments: 'YES',
            commentwriting: 'YES',
            moretext: 'YES',
            sendnews: 'YES',
            newssending: 'YES',
            smilies: 'YES',
            bbcode: 'YES',
            html: 'NO',
            dateformat: 'd.m.Y',
            timeformat: 'H:i',
            url: 'http://example.com',
            email: 'test@example.com',
            relatedlinks: 'YES',
            template: 1,
            headlines: 10,
            news: 5,
            spamprotection: 1,
            relatedlinks_num: 5
        );

        $this->assertSame('YES', $cd->categories);
        $this->assertSame('YES', $cd->categorypics);
        $this->assertSame('YES', $cd->comments);
        $this->assertSame('d.m.Y', $cd->dateformat);
        $this->assertSame('H:i', $cd->timeformat);
        $this->assertSame('http://example.com', $cd->url);
        $this->assertSame('test@example.com', $cd->email);
        $this->assertSame(1, $cd->template);
        $this->assertSame(10, $cd->headlines);
        $this->assertSame(5, $cd->news);
        $this->assertSame(1, $cd->spamprotection);
        $this->assertSame(5, $cd->relatedlinks_num);
    }

    #[Test]
    public function configDataIsValidReturnsTrueWhenEmailAndUrlSet(): void
    {
        $cd = new \ConfigData(
            categories: 'YES',
            categorypics: 'NO',
            comments: 'YES',
            commentwriting: 'NO',
            moretext: 'YES',
            sendnews: 'NO',
            newssending: 'NO',
            smilies: 'YES',
            bbcode: 'YES',
            html: 'NO',
            dateformat: 'd.m.Y',
            timeformat: 'H:i',
            url: 'http://example.com',
            email: 'admin@example.com',
            relatedlinks: 'NO',
            template: 1,
            headlines: 10,
            news: 5,
            spamprotection: 0,
            relatedlinks_num: 3
        );

        $this->assertTrue($cd->isValid());
    }

    #[Test]
    public function configDataIsValidReturnsFalseWhenEmailEmpty(): void
    {
        $cd = new \ConfigData(
            categories: 'YES',
            categorypics: 'NO',
            comments: 'YES',
            commentwriting: 'NO',
            moretext: 'YES',
            sendnews: 'NO',
            newssending: 'NO',
            smilies: 'YES',
            bbcode: 'YES',
            html: 'NO',
            dateformat: 'd.m.Y',
            timeformat: 'H:i',
            url: 'http://example.com',
            email: '',
            relatedlinks: 'NO',
            template: 1,
            headlines: 10,
            news: 5,
            spamprotection: 0,
            relatedlinks_num: 3
        );

        $this->assertFalse($cd->isValid());
    }

    #[Test]
    public function configDataIsValidReturnsFalseWhenUrlEmpty(): void
    {
        $cd = new \ConfigData(
            categories: 'YES',
            categorypics: 'NO',
            comments: 'YES',
            commentwriting: 'NO',
            moretext: 'YES',
            sendnews: 'NO',
            newssending: 'NO',
            smilies: 'YES',
            bbcode: 'YES',
            html: 'NO',
            dateformat: 'd.m.Y',
            timeformat: 'H:i',
            url: '',
            email: 'admin@example.com',
            relatedlinks: 'NO',
            template: 1,
            headlines: 10,
            news: 5,
            spamprotection: 0,
            relatedlinks_num: 3
        );

        $this->assertFalse($cd->isValid());
    }

    #[Test]
    public function configDataIsValidReturnsFalseWhenBothEmailAndUrlEmpty(): void
    {
        $cd = new \ConfigData(
            categories: 'YES',
            categorypics: 'NO',
            comments: 'YES',
            commentwriting: 'NO',
            moretext: 'YES',
            sendnews: 'NO',
            newssending: 'NO',
            smilies: 'YES',
            bbcode: 'YES',
            html: 'NO',
            dateformat: 'd.m.Y',
            timeformat: 'H:i',
            url: '',
            email: '',
            relatedlinks: 'NO',
            template: 1,
            headlines: 10,
            news: 5,
            spamprotection: 0,
            relatedlinks_num: 3
        );

        $this->assertFalse($cd->isValid());
    }

    #[Test]
    public function configDataFromPostCreatesInstance(): void
    {
        $_POST = [
            'categories' => 'YES',
            'categorypics' => 'NO',
            'comments' => 'YES',
            'commentwriting' => 'NO',
            'moretext' => 'YES',
            'sendnews' => 'NO',
            'newssending' => 'NO',
            'smilies' => 'YES',
            'bbcode' => 'YES',
            'html' => 'NO',
            'dateformat' => 'd.m.Y',
            'timeformat' => 'H:i',
            'url' => 'http://example.com',
            'email' => 'admin@example.com',
            'relatedlinks' => 'YES',
            'template' => '1',
            'headlines' => '10',
            'news' => '5',
            'spamprotection' => '1',
            'relatedlinks_num' => '3',
        ];

        $cd = \ConfigData::fromPost();

        $this->assertInstanceOf(\ConfigData::class, $cd);
        $this->assertSame('http://example.com', $cd->url);
        $this->assertSame('admin@example.com', $cd->email);
        $this->assertTrue($cd->isValid());
    }

    #[Test]
    public function configDataFromPostHandlesMissingKeys(): void
    {
        $_POST = [];

        $cd = \ConfigData::fromPost();

        $this->assertInstanceOf(\ConfigData::class, $cd);
        $this->assertFalse($cd->isValid());
    }

    #[Test]
    public function configDataIntFieldsAreIntegers(): void
    {
        $cd = new \ConfigData(
            categories: 'YES',
            categorypics: 'NO',
            comments: 'YES',
            commentwriting: 'NO',
            moretext: 'YES',
            sendnews: 'NO',
            newssending: 'NO',
            smilies: 'YES',
            bbcode: 'YES',
            html: 'NO',
            dateformat: 'd.m.Y',
            timeformat: 'H:i',
            url: 'http://example.com',
            email: 'admin@example.com',
            relatedlinks: 'YES',
            template: 2,
            headlines: 15,
            news: 8,
            spamprotection: 1,
            relatedlinks_num: 10
        );

        $this->assertIsInt($cd->template);
        $this->assertIsInt($cd->headlines);
        $this->assertIsInt($cd->news);
        $this->assertIsInt($cd->spamprotection);
        $this->assertIsInt($cd->relatedlinks_num);
    }

    #[Test]
    public function configDataStringFieldsAreStrings(): void
    {
        $cd = new \ConfigData(
            categories: 'YES',
            categorypics: 'NO',
            comments: 'YES',
            commentwriting: 'NO',
            moretext: 'YES',
            sendnews: 'NO',
            newssending: 'NO',
            smilies: 'YES',
            bbcode: 'YES',
            html: 'NO',
            dateformat: 'd.m.Y',
            timeformat: 'H:i',
            url: 'http://example.com',
            email: 'admin@example.com',
            relatedlinks: 'YES',
            template: 1,
            headlines: 10,
            news: 5,
            spamprotection: 0,
            relatedlinks_num: 3
        );

        $this->assertIsString($cd->categories);
        $this->assertIsString($cd->categorypics);
        $this->assertIsString($cd->comments);
        $this->assertIsString($cd->commentwriting);
        $this->assertIsString($cd->moretext);
        $this->assertIsString($cd->sendnews);
        $this->assertIsString($cd->newssending);
        $this->assertIsString($cd->smilies);
        $this->assertIsString($cd->bbcode);
        $this->assertIsString($cd->html);
        $this->assertIsString($cd->dateformat);
        $this->assertIsString($cd->timeformat);
        $this->assertIsString($cd->url);
        $this->assertIsString($cd->email);
        $this->assertIsString($cd->relatedlinks);
    }

    #[Test]
    public function configDataFromPostConvertsIntFields(): void
    {
        $_POST = [
            'categories' => 'YES',
            'categorypics' => 'NO',
            'comments' => 'YES',
            'commentwriting' => 'NO',
            'moretext' => 'NO',
            'sendnews' => 'NO',
            'newssending' => 'NO',
            'smilies' => 'NO',
            'bbcode' => 'NO',
            'html' => 'NO',
            'dateformat' => 'd.m.Y',
            'timeformat' => 'H:i',
            'url' => 'http://example.com',
            'email' => 'admin@example.com',
            'relatedlinks' => 'NO',
            'template' => '3',
            'headlines' => '20',
            'news' => '12',
            'spamprotection' => '0',
            'relatedlinks_num' => '7',
        ];

        $cd = \ConfigData::fromPost();

        $this->assertIsInt($cd->template);
        $this->assertIsInt($cd->headlines);
        $this->assertIsInt($cd->news);
        $this->assertIsInt($cd->spamprotection);
        $this->assertIsInt($cd->relatedlinks_num);
    }
}
