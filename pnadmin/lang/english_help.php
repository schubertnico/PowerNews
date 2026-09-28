<?php

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

/* English help file written by PowerScripts (Stefan Kraemer) */
/* PowerNews 3.12; german-du_help.php and german-sie_help.php have the same structure. */
?>
<div id="pn-help-top"></div>

<p class="lead">This help describes the PowerNews admin area. Click a section to jump straight to its explanation.</p>

<nav class="card mb-4">
    <div class="card-body">
        <h2 class="h6 fw-bold mb-2">Contents</h2>
        <ul class="mb-0">
            <li><a href="#help-start">Start page</a></li>
            <li><a href="#help-news">News</a></li>
            <li><a href="#help-categories">Categories</a></li>
            <li><a href="#help-users">Users</a></li>
            <li><a href="#help-permissions">Permissions</a></li>
            <li><a href="#help-templates">Templates</a></li>
            <li><a href="#help-configuration">Configuration</a></li>
            <li><a href="#help-profile">Your profile</a></li>
            <li><a href="#help-other">Other (BB code, smilies, license)</a></li>
        </ul>
    </div>
</nav>

<!-- ==================== START PAGE ==================== -->
<section id="help-start" class="mb-4">
    <h2 class="h5 fw-bold border-bottom pb-2">Start page</h2>
    <p>The start page shows what needs attention: <strong>To review</strong> gives the number of news submitted by visitors with the status <em>Unchecked</em>, and <em>Review submissions</em> leads straight to the filtered news list. <strong>Comments</strong> counts the new comments of the last 7 days and links the five newest.</p>
    <p>Navigation, quick links and the start page only show what your account is allowed to do. Without read permission a section does not appear in the menu; opening it directly ends with "Access denied".</p>
    <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
</section>

<!-- ==================== NEWS ==================== -->
<section id="help-news" class="mb-4">
    <h2 class="h5 fw-bold border-bottom pb-2">News</h2>
    <ul>
        <li><a href="#help-news-add">Add news</a></li>
        <li><a href="#help-news-show">Show and filter news</a></li>
        <li><a href="#help-news-edit">Edit and delete news, moderate comments</a></li>
        <li><a href="#help-news-search">Search news</a></li>
        <li><a href="#help-news-status">Status explained</a></li>
    </ul>

    <div id="help-news-add" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Add news</h3>
        <p>Use <a href="index.php?page=news&amp;subpage=add">News &gt; Add news</a> to create a new entry. <strong>Title</strong> and <strong>text</strong> are required; if categories are enabled, a <strong>category</strong> is required as well. Only active categories are offered.</p>
        <p>Choose the publishing date with day, month, year, hour and minute. If it lies in the future, the news appears on the start page and in the archive only from then on.</p>
        <p>If <em>text splitting</em> is enabled in the configuration, you can add a <strong>long text</strong> that only appears on the detail page. If <em>related links</em> are enabled, enter a title, an address (http://, https:// or a relative path) and a target for each link: <em>New window</em> or <em>Same window</em>.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-news-show" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Show and filter news</h3>
        <p><a href="index.php?page=news&amp;subpage=show">News &gt; Show news</a> lists all entries, newest first, 25 per page. Above the list you can filter by status: <em>All</em>, <em>Unchecked</em> (with count), <em>Activated</em> or <em>Deactivated</em>. Click a title to edit the entry.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-news-edit" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Edit and delete news, moderate comments</h3>
        <p>Pick a news entry from the <a href="index.php?page=news&amp;subpage=show">news list</a> or the <a href="index.php?page=news&amp;subpage=search">search results</a>. You can change all fields, the category, the publishing date and the status. If the news belongs to a category that has since been deactivated, that category stays selected and is marked "(deactivated)"; saving does not move the news to another category.</p>
        <p>The red box <strong>Delete</strong> removes the entry and its comments <strong>permanently</strong>. To only hide a news entry, set its status to <em>Deactivated</em>.</p>
        <p>If comments are enabled, they are listed below the form in chronological order, oldest first. With the permission <em>write comments</em> you can revise texts and delete single comments with the red checkbox; only comments you actually changed are saved.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-news-search" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Search news</h3>
        <p><a href="index.php?page=news&amp;subpage=search">News &gt; Search news</a> searches the title, the text, the ID or – if enabled – the long text. The result is shown as a table like <a href="#help-news-show">Show news</a>.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-news-status" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Status explained</h3>
        <ul>
            <li><span class="badge text-bg-success">Activated</span> &ndash; The news is published, visible in the frontend and open for comments (once the publishing date has been reached).</li>
            <li><span class="badge text-bg-warning">Unchecked</span> &ndash; A visitor submitted the news; it waits for your approval and does not appear in the frontend.</li>
            <li><span class="badge text-bg-danger">Deactivated</span> &ndash; The news is not visible in the frontend.</li>
        </ul>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>
</section>

<!-- ==================== CATEGORIES ==================== -->
<section id="help-categories" class="mb-4">
    <h2 class="h5 fw-bold border-bottom pb-2">Categories</h2>
    <p>Categories are only available if the option <em>categories</em> is enabled in the <a href="#help-configuration-categories">configuration</a>.</p>
    <ul>
        <li><a href="#help-categories-add">Add a category</a></li>
        <li><a href="#help-categories-edit">Edit a category</a></li>
        <li><a href="#help-categories-deactivate">Deactivate instead of delete</a></li>
    </ul>

    <div id="help-categories-add" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Add a category</h3>
        <p>Use <a href="index.php?page=categories&amp;subpage=add">Categories &gt; Add category</a> to enter a title and a description (at most 255 characters). If <em>category pictures</em> are enabled, you can also upload a picture (GIF, JPG or PNG, at most 2&nbsp;MB) that templates show via <code>{CATPIC}</code>.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-categories-edit" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Edit a category</h3>
        <p>Pick the entry in the <a href="index.php?page=categories&amp;subpage=show">category list</a> and change its title, description and status. For a new picture, first tick <em>Upload picture</em> and then choose the file.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-categories-deactivate" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Deactivate instead of delete</h3>
        <p>Categories cannot be deleted because the news assigned to them would be orphaned. Set the category's status to <em>Deactivated</em> instead: it disappears from the selection for new news and from the submission form. Existing news keep their category, even when you edit them.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>
</section>

<!-- ==================== USERS ==================== -->
<section id="help-users" class="mb-4">
    <h2 class="h5 fw-bold border-bottom pb-2">Users</h2>
    <ul>
        <li><a href="#help-users-add">Add a user (invitation)</a></li>
        <li><a href="#help-users-show">User list</a></li>
        <li><a href="#help-users-search">Search users</a></li>
        <li><a href="#help-users-edit">Edit / deactivate a user</a></li>
    </ul>

    <div id="help-users-add" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Add a user (invitation)</h3>
        <p>Users usually register themselves via <code>user.php</code> and choose their password there. With <a href="index.php?page=users&amp;subpage=add">Users &gt; Add user</a> you can still create an account yourself, for example for a new editor.</p>
        <p>PowerNews never sends a <strong>password</strong>. The new account receives an invitation with a one-time link that lets the user choose a password; the link is valid for 48 hours. Until then, logging in is not possible. With <em>Send invitation by e-mail</em> the link is sent by mail; without the tick PowerNews shows it to you once after saving so you can pass it on yourself. If the link has expired, "Forgot password" on the website or <em>Send link for a new password</em> when editing the user helps.</p>
        <p>Grant admin rights afterwards under <a href="#help-permissions">Permissions</a>.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-users-show" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">User list</h3>
        <p><a href="index.php?page=users&amp;subpage=show">Users &gt; Show users</a> lists all accounts, 25 per page. The column <strong>Linked to e-mail</strong> shows whether the user's name below news and comments becomes a mail link, <strong>Admin</strong> whether permissions are assigned and <strong>Status</strong> whether the account is active.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-users-search" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Search users</h3>
        <p><a href="index.php?page=users&amp;subpage=search">Users &gt; Search user</a> searches by nickname, e-mail address or ID. Click a nickname in the result to edit the account.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-users-edit" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Edit / deactivate a user</h3>
        <p>Click the nickname in the list. You can edit the nickname, the e-mail address, whether the name is linked to the e-mail address, and the status. <em>Send link for a new password</em> sends the user a one-time link to choose a new password; until then the current password stays valid. If the account has no password yet, the invitation is sent again instead. With <em>Send e-mail</em> the user receives the changed data (without password) by mail.</p>
        <p>Accounts cannot be deleted. Set the status to <em>Deactivated</em>: the user can no longer log in, write comments or submit news, and current sessions end immediately. Deactivated accounts cannot receive admin rights.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>
</section>

<!-- ==================== PERMISSIONS ==================== -->
<section id="help-permissions" class="mb-4">
    <h2 class="h5 fw-bold border-bottom pb-2">Permissions</h2>
    <p>Permissions define which sections of the admin area a user may read or write. There are seven sections – templates, configuration, users, permissions, categories, news, comments – each with separate read and write permissions. The read permission decides whether a section appears in the navigation.</p>
    <ul>
        <li><a href="#help-permissions-add">Add permissions</a></li>
        <li><a href="#help-permissions-show">Show permissions</a></li>
        <li><a href="#help-permissions-edit">Edit / delete permissions</a></li>
    </ul>

    <div id="help-permissions-add" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Add permissions</h3>
        <p>Use <a href="index.php?page=permissions&amp;subpage=add">Permissions &gt; Add permissions</a> to enter the nickname of an existing user and set the read and write permissions. <em>News</em> and <em>comments</em> are preselected, which is all an editor needs. <em>Write permissions</em> and <em>write templates</em> effectively make someone an admin. The user is not informed about the new rights automatically.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-permissions-show" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Show permissions</h3>
        <p>The <a href="index.php?page=permissions&amp;subpage=show">permission list</a> shows all admin accounts with an overview of their read and write permissions. <span class="badge text-bg-success">&check;</span> means allowed, <span class="badge text-bg-secondary">&minus;</span> not allowed.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-permissions-edit" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Edit / delete permissions</h3>
        <p>Click the nickname in the list to adjust the permissions. The red box <strong>Delete</strong> removes all admin rights of the user; the user account itself remains.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>
</section>

<!-- ==================== TEMPLATES ==================== -->
<section id="help-templates" class="mb-4">
    <h2 class="h5 fw-bold border-bottom pb-2">Templates</h2>
    <p>Templates control the HTML output (headlines, news, comments, forms) and the texts of the e-mails.</p>
    <ul>
        <li><a href="#help-templates-add">Add a template</a></li>
        <li><a href="#help-templates-show">Show templates</a></li>
        <li><a href="#help-templates-edit">Edit / delete a template</a></li>
        <li><a href="#help-templates-default">Default template</a></li>
    </ul>

    <div id="help-templates-add" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Add a template</h3>
        <p>Enter a title under <a href="index.php?page=templates&amp;subpage=add">Templates &gt; Add template</a>. PowerNews copies the default template as content, so you start from a complete, working template.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-templates-show" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Show templates</h3>
        <p><a href="index.php?page=templates&amp;subpage=show">Templates &gt; Show templates</a> lists all templates. Click a name to edit the template.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-templates-edit" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Edit / delete a template</h3>
        <p>The editor groups the parts into three sections:</p>
        <ul>
            <li><strong>Output</strong> &ndash; message, headline, news, comment, user menus, related link.</li>
            <li><strong>Forms and input</strong> &ndash; comment, registration (with the password fields <code>pndata[password]</code> and <code>pndata[password2]</code>), login, logout, forgot password, profile, archive and news submission.</li>
            <li><strong>E-mails</strong> &ndash; invitation, data change, registration and forgot password.</li>
        </ul>
        <p>Placeholders such as <code>{ID}</code>, <code>{TITLE}</code>, <code>{DATE}</code>, <code>{TIME}</code>, <code>{AUTHOR}</code>, <code>{TEXT}</code>, <code>{NICKNAME}</code>, <code>{EMAIL}</code> and <code>{CSRF}</code> are replaced; the placeholders valid for each field are listed below it. The e-mails also offer <code>{SITE}</code> (name of the website), <code>{URL}</code>, <code>{LOGINLINK}</code>, <code>{INVITELINK}</code> with <code>{VALIDHOURS}</code> (invitation) and <code>{RESETLINK}</code> with <code>{VALIDMINUTES}</code> (forgot password). PowerNews never sends passwords; a line with <code>{PASSWORD}</code> from older templates is left out. If an e-mail lacks its link placeholder, the default text from the language file is used.</p>
        <p>The texts of the default template are written in German without addressing the reader in the familiar or formal form. The red box <strong>Delete</strong> removes a template completely; make sure beforehand that it is not active in the <a href="#help-configuration-template">configuration</a>.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-templates-default" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Default template</h3>
        <p>You can edit the <a href="index.php?page=templates&amp;subpage=edit&amp;templateid=1">default template</a> (ID&nbsp;1), but not delete it: it is the basis for every new template. Changes to it therefore also affect all templates created later.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>
</section>

<!-- ==================== CONFIGURATION ==================== -->
<section id="help-configuration" class="mb-4">
    <h2 class="h5 fw-bold border-bottom pb-2">Configuration</h2>
    <p>The <a href="index.php?page=configuration">configuration</a> holds the global settings. Changes take effect in the frontend immediately.</p>
    <ul>
        <li><a href="#help-configuration-categories">Categories</a></li>
        <li><a href="#help-configuration-catpics">Category pictures</a></li>
        <li><a href="#help-configuration-comments">Comments</a></li>
        <li><a href="#help-configuration-writecomments">Who may write comments?</a></li>
        <li><a href="#help-configuration-moretext">Text splitting (short / long)</a></li>
        <li><a href="#help-configuration-sendnews">Allow news submission</a></li>
        <li><a href="#help-configuration-newssending">Who may submit news?</a></li>
        <li><a href="#help-configuration-smilies">Smilies</a></li>
        <li><a href="#help-configuration-bbcode">BB code</a></li>
        <li><a href="#help-configuration-html">HTML</a></li>
        <li><a href="#help-configuration-dateformat">Date &amp; time format</a></li>
        <li><a href="#help-configuration-template">Active template</a></li>
        <li><a href="#help-configuration-url">URL</a></li>
        <li><a href="#help-configuration-email">Sender e-mail</a></li>
        <li><a href="#help-configuration-headlines">Number of headlines</a></li>
        <li><a href="#help-configuration-news">Number of news on the start page</a></li>
        <li><a href="#help-configuration-spamprotection">Spam protection</a></li>
        <li><a href="#help-configuration-relatedlinks">Related links</a></li>
        <li><a href="#help-configuration-relatedlinks-num">Number of related links</a></li>
    </ul>

    <div id="help-configuration-categories" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Categories</h3>
        <p>Turns categories on or off. If they are on, writing news requires a category.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-configuration-catpics" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Category pictures</h3>
        <p>Allows pictures for categories, which templates show via <code>{CATPIC}</code>. The option only appears if <em>categories</em> are enabled.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-configuration-comments" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Comments</h3>
        <p>Turns comments on or off globally.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-configuration-writecomments" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Who may write comments?</h3>
        <p>If comments are enabled, choose between <em>guests &amp; registered users</em> and <em>registered users</em>.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-configuration-moretext" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Text splitting (short / long)</h3>
        <p>Allows a short text for the start page and a long text that also appears on the detail page.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-configuration-sendnews" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Allow news submission</h3>
        <p>Turns the frontend form <code>sendnews.php</code> on or off.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-configuration-newssending" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Who may submit news?</h3>
        <p>Choose between <em>guests &amp; registered users</em> and <em>registered users</em>. Submitted news are stored with the status <em>Unchecked</em>; the start page of the admin area shows how many await your approval.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-configuration-smilies" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Smilies</h3>
        <p>Defines where smilie codes (see <a href="#help-other-smilies">Other &gt; Smilies</a>) are shown as pictures: <em>No</em>, <em>Comments</em>, <em>News</em> or <em>Comments &amp; news</em>.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-configuration-bbcode" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">BB code</h3>
        <p>Like smilies, this defines where BB codes such as <code>[b]</code>, <code>[i]</code> and <code>[url]</code> are evaluated. The supported codes are listed under <a href="#help-other-bbcode">Other &gt; BB code</a>.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-configuration-html" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">HTML</h3>
        <p>For security reasons, HTML in news and comments is never evaluated but shown as text. The setting stays stored for compatibility but no longer appears in the form. Use BB code for formatting.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-configuration-dateformat" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Date &amp; time format</h3>
        <p>Both fields take a format of at most 50 characters. PowerNews understands two notations:</p>
        <ul>
            <li><strong>date() notation</strong> (recommended), e.g. <code>d.m.Y</code> and <code>H:i</code>. Letters without meaning need a preceding backslash, e.g. <code>g:i \o\'\c\l\o\c\k</code>.</li>
            <li><strong>strftime notation</strong> with percent signs, e.g. <code>%d.%m.%Y</code> and <code>%H:%M</code> (default of new installations). PowerNews converts it to the date() notation internally.</li>
        </ul>
        <p>Weekdays and month names appear in the language of the installation.</p>
        <table class="table table-sm align-middle mt-2">
            <thead>
                <tr><th>date()</th><th>strftime</th><th>Meaning</th><th>Example</th></tr>
            </thead>
            <tbody>
                <tr><td><code>d</code></td><td><code>%d</code></td><td>Day, two digits</td><td>07</td></tr>
                <tr><td><code>j</code></td><td><code>%e</code></td><td>Day without leading zero</td><td>7</td></tr>
                <tr><td><code>l</code> / <code>D</code></td><td><code>%A</code> / <code>%a</code></td><td>Weekday / short</td><td>Sunday / Sun</td></tr>
                <tr><td><code>m</code></td><td><code>%m</code></td><td>Month, two digits</td><td>03</td></tr>
                <tr><td><code>F</code> / <code>M</code></td><td><code>%B</code> / <code>%b</code></td><td>Month name / short</td><td>March / Mar</td></tr>
                <tr><td><code>Y</code></td><td><code>%Y</code></td><td>Year, four digits</td><td>2026</td></tr>
                <tr><td><code>H</code></td><td><code>%H</code></td><td>Hour (00–23)</td><td>17</td></tr>
                <tr><td><code>i</code></td><td><code>%M</code></td><td>Minute (00–59)</td><td>30</td></tr>
            </tbody>
        </table>
        <p class="mb-0">All characters of the date() notation are listed in the <a href="https://www.php.net/manual/en/datetime.format.php" rel="noopener noreferrer">PHP manual</a>.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-configuration-template" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Active template</h3>
        <p>Selects the template used in the frontend. Create your own templates under <a href="#help-templates-add">Templates &gt; Add template</a>.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-configuration-url" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">URL</h3>
        <p>Full address of the PowerNews directory without a trailing slash, e.g. <em>https://www.example.org/news</em> if the admin area is located at <em>https://www.example.org/news/pnadmin</em>. It is used for the links in all e-mails (login, invitation, forgot password) and for the website name in the subject.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-configuration-email" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Sender e-mail</h3>
        <p>Sender address of all automatic mails (registration, invitation, data change, forgot password). A correct, deliverable address is important so that the mails do not end up in spam.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-configuration-headlines" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Number of headlines</h3>
        <p>How many headlines the file <code>pninc/headlines.inc.php</code> shows where it is included (1 to 99).</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-configuration-news" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Number of news on the start page</h3>
        <p>How many news the start page shows (1 to 99). Older entries remain available in the archive.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-configuration-spamprotection" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Spam protection</h3>
        <p>Minimum pause in seconds between two comments from the same IP address. Allowed are 0 to 86400 seconds (one day); 0 turns the lock off.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-configuration-relatedlinks" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Related links</h3>
        <p>Allows a list of related links for each news entry. The template decides where it appears via <code>{RELATEDLINKS}</code>.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-configuration-relatedlinks-num" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Number of related links</h3>
        <p>How many input rows for related links the news forms offer (1 to 20).</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>
</section>

<!-- ==================== YOUR PROFILE ==================== -->
<section id="help-profile" class="mb-4">
    <h2 class="h5 fw-bold border-bottom pb-2">Your profile</h2>
    <p>Use <em>Edit profile</em> at the top right or go to <a href="index.php?page=profile">Profile</a> to edit your own account.</p>
    <p><strong>Nickname</strong> and <strong>e-mail</strong> are required. With <em>Link name to e-mail address (publicly visible)</em> your name below your news and comments becomes a mail link; the address is then visible to all visitors. There is no public profile. Leave both password fields empty if you do not want to change your password; a new password needs at least 8 characters and ends all sessions.</p>
    <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
</section>

<!-- ==================== OTHER ==================== -->
<section id="help-other" class="mb-4">
    <h2 class="h5 fw-bold border-bottom pb-2">Other</h2>
    <ul>
        <li><a href="#help-other-bbcode">BB code reference</a></li>
        <li><a href="#help-other-smilies">Smilies</a></li>
        <li><a href="#help-other-license">License</a></li>
        <li><a href="#help-other-external">External page</a></li>
        <li><a href="#help-other-about">About PowerNews</a></li>
    </ul>

    <div id="help-other-bbcode" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">BB code reference</h3>
        <p>The following BB codes work in news and comments if they are enabled in the <a href="#help-configuration-bbcode">configuration</a>.</p>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr><th>Input</th><th>Output</th></tr>
                </thead>
                <tbody>
                    <tr><td><code>[b]PowerNews[/b]</code></td><td><strong>PowerNews</strong></td></tr>
                    <tr><td><code>[u]PowerNews[/u]</code></td><td><u>PowerNews</u></td></tr>
                    <tr><td><code>[i]PowerNews[/i]</code></td><td><em>PowerNews</em></td></tr>
                    <tr><td><code>[url]https://www.example.org[/url]</code></td><td><a href="https://www.example.org" rel="noopener noreferrer">https://www.example.org</a></td></tr>
                    <tr><td><code>[url]www.example.org[/url]</code></td><td><a href="https://www.example.org" rel="noopener noreferrer">www.example.org</a> (without a scheme, https:// is used)</td></tr>
                    <tr><td><code>[url=https://www.example.org]Example[/url]</code></td><td><a href="https://www.example.org" rel="noopener noreferrer">Example</a></td></tr>
                    <tr><td><code>[email]info@example.org[/email]</code></td><td><a href="mailto:info@example.org">info@example.org</a></td></tr>
                    <tr><td><code>[img]https://www.example.org/picture.png[/img]</code></td><td>(picture from your own domain – otherwise the code stays as text)</td></tr>
                </tbody>
            </table>
        </div>
        <p class="form-text">Links open in a new window. <code>[url]</code> only accepts http and https addresses; other schemes such as <code>javascript:</code> stay as text. For security reasons <code>[img]</code> only shows pictures from your own domain.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-other-smilies" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Smilies</h3>
        <p>These smilie codes are replaced if smilies are enabled in the <a href="#help-configuration-smilies">configuration</a>:</p>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr><th>Input</th><th>Output</th></tr>
                </thead>
                <tbody>
                    <tr><td><code>:)</code></td><td><img src="../pngfx/smilies/smile.gif" width="15" height="15" alt="smile"></td></tr>
                    <tr><td><code>;)</code></td><td><img src="../pngfx/smilies/wink.gif" width="15" height="15" alt="wink"></td></tr>
                    <tr><td><code>:))</code></td><td><img src="../pngfx/smilies/laugh.gif" width="15" height="15" alt="laugh"></td></tr>
                    <tr><td><code>:D</code></td><td><img src="../pngfx/smilies/bigsmile.gif" width="15" height="15" alt="big smile"></td></tr>
                    <tr><td><code>:P</code></td><td><img src="../pngfx/smilies/tongue.gif" width="15" height="15" alt="tongue"></td></tr>
                    <tr><td><code>:(</code></td><td><img src="../pngfx/smilies/sad.gif" width="15" height="15" alt="sad"></td></tr>
                    <tr><td><code>:?:</code></td><td><img src="../pngfx/smilies/confused.gif" width="15" height="22" alt="confused"></td></tr>
                </tbody>
            </table>
        </div>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-other-license" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">License</h3>
        <p>PowerNews is released under the MIT license. The full text is available under <a href="index.php?page=other&amp;subpage=license">Other &gt; License</a>.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-other-external" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">External page</h3>
        <p>The button <em>External page</em> at the top right leads to the start page of the website – handy for checking a change from the visitor's point of view.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>

    <div id="help-other-about" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">About PowerNews</h3>
        <p>PowerNews is a news script for PHP and MySQL/MariaDB by the development group <a href="https://www.powerscripts.org" rel="noopener noreferrer">PowerScripts</a>, originally written by Stefan Krämer. Version 3 uses Bootstrap 5 and includes a user and permission system.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Back to top</a></p>
    </div>
</section>
