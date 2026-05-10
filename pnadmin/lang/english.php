<?php

declare(strict_types=1);

/* PowerNews is a PHP and mySQL based newsscript - www.powerscripts.org */
/* Copyright (C) 2001-2026 PowerScripts                                 */

/* This program is free software; you can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 2 of the License, or    */
/* (at your option) any later version.                                  */

/* This program is distributed in the hope that it will be useful,      */
/* but WITHOUT ANY WARRANTY; without even the implied warranty of       */
/* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        */
/* GNU General Public License for more details.                         */

/* You should have received a copy of the GNU General Public License    */
/* along with this program; if not, write to the Free Software          */
/* Foundation, Inc., 59 Temple Place, Suite 330, Boston,                */
/* MA  02111-1307  USA                                                  */

/* This is a standard admin language file - you can edit all admin outputs from here */

/* English language file written by PowerScripts (Stefan Kraemer) */

/* Categories */
define('L_CAT_TITLE_CATEGORIES', 'C A T E G O R I E S');
define('L_CAT_ADDCAT', 'Add category');
define('L_CAT_SHOWCATS', 'Show categories');
define('L_CAT_FILLALL', 'Please insert title and description');
define('L_CAT_ANDPIC', 'and upload a picture');
define('L_CAT_CATADDED', 'Category was added successfully!');
define('L_CAT_TITLE', 'Title');
define('L_CAT_TITLEDESC', "Name of the category (No ./\:*?&lt;&gt;|\")");
define('L_CAT_DESCRIPTION', 'Description');
define('L_CAT_DESCRIPTIONDESC', 'Description for the category (max. 255 characters/HTML allowed)');
define('L_CAT_PIC', 'Picture');
define('L_CAT_PICDESC', 'Picture for the new category');
define('L_CAT_ADDNEWCAT', 'Add new category');
define('L_CAT_OTHERCATWITHTITLEEXISTS', 'Category with this title already exists!');
define('L_CAT_WRONGCATTITLE', 'Invalid title!');
define('L_CAT_PICUPLOADERROR', 'Upload of the picture failed!');
define('L_CAT_CATADDERROR', 'Adding of category failed!');
define('L_CAT_NOCATSAVAILABLE', 'No categories');
define('L_CAT_CATSAREDEACTIVATED', 'Categories are deactivated!');
define('L_CAT_EDITED', 'Category edited successfully');
define('L_CAT_EDITCAT', 'Edit category');
define('L_CAT_UPLOADPIC', 'Upload picture');
define('L_CAT_UPLOADPICDESC', 'Please click here if you want to upload a <b>new</b> picture');
define('L_CAT_CURRENTPIC', 'Current picture');
define('L_CAT_STATUS', 'Status');
define('L_CAT_STATUSDESC', 'The status of this category');
define('L_CAT_CHOOSECAT', 'Please choose a category');
define('L_CAT_NONEXISTINGCAT', "The chosen category doesn't exist!");
define('L_CAT_CANNOTDELETEOLDPIC', 'The old picture can not be deleted!');
define('L_CAT_PICRENAMEERROR', 'Renaming the picture failed!');
define('L_CAT_CATEDITERROR', 'Editing category failed!');
define('L_CAT_CLICKFORDETAILS', 'Please click on the title of a category for edit!');
define('L_CAT_PICSONLYINTHISFORMAT', 'Pictures may only be uploaded in GIF/JPG/JPEG/PNG format!');

/* Configuration */
define('L_TITLE_CONFIGURATION', 'C O N F I G U R A T I O N');
define('L_CONF_FILLALL', 'You have to fill in all fields!');
define('L_CONF_EDITED', 'The configuration was edited successfully!');
define('L_CONF_CATEGORIES', 'Categories');
define('L_CONF_CATEGORIES_DESC', 'Do you want to use categories?');
define('L_CONF_CATPICS', 'Category pictures');
define('L_CONF_CATPICS_DESC', 'Do you want to use pictures for the categories?');
define('L_CONF_COMMENTS', 'Comments');
define('L_CONF_COMMENTS_DESC', 'Do you want to use comments for the news?');
define('L_CONF_WRITECOMMENTS', 'Comment-writing');
define('L_CONF_WRITECOMMENTS_DESC', 'Who is allowed to write comments?');
define('L_CONF_GUESTSANDREGS', 'Guests & Registered');
define('L_CONF_REGS', 'Registered');
define('L_CONF_MORETEXT', 'Textsplitting');
define('L_CONF_MORETEXT_DESC', 'Do you want to split the news into a short and a long text?');
define('L_CONF_SENDIN', 'Send in function');
define('L_CONF_SENDIN_DESC', 'Do you want normal users to be able to send in news?');
define('L_CONF_SENDNEWS', 'Send in News');
define('L_CONF_SENDNEWS_DESC', 'Who is allowed to send in news?');
define('L_CONF_SMILIES', 'Smilies');
define('L_CONF_SMILIES_DESC', 'Allow the use of graphical smilies?');
define('L_CONF_COMMENTSANDNEWS', 'Comments & News');
define('L_CONF_NEWS', 'News');
define('L_CONF_BBCODE', 'BB Code');
define('L_CONF_BBCODE_DESC', 'Allow to use BB Code?');
define('L_CONF_HTML', 'HTML');
define('L_CONF_HTML_DESC', 'Allow to use HTML?');
define('L_CONF_DATEFORMAT', 'Date format');
define('L_CONF_DATEFORMAT_DESC', 'How to format the date? (<a href="./?page=other&subpage=help#configuration.dateformat">Help</a>)');
define('L_CONF_TEMPLATE', 'Template');
define('L_CONF_TEMPLATE_DESC', 'What template should be used?');
define('L_CONF_URL', 'URL');
define('L_CONF_URL_DESC', 'The URL to your homepage (without / at the end)');
define('L_CONF_EMAIL', 'E-Mail');
define('L_CONF_EMAIL_DESC', "Webmaster's E-Mail address");
define('L_CONF_HEADLINES', 'Headlines');
define('L_CONF_HEADLINES_DESC', 'Number of headlines');
define('L_CONF_NEWS_DESC', 'Number of news');
define('L_CONF_SPAMPROTECT', 'Spam protection');
define('L_CONF_SPAMPROTECT_DESC', 'How many seconds have to elapse until you can post another comment?');
define('L_CONF_EDITCONFIG', 'Edit configuration');
define('L_CONF_ONLYNUMBERS', 'Headlines, news and spam protection must be numbers!');
define('L_CONF_WRONGURL', 'The URL seems to be incorrect!');
define('L_CONF_WRONGEMAIL', 'The E-Mail address seems to be incorrect!');
define('L_CONF_NOTEMPLATES', 'No existing templates');
define('L_CONF_RELATEDLINKS', 'Related Links');
define('L_CONF_RELATEDLINKS_DESC', 'Do you want to use related links?');
define('L_CONF_RELATEDLINKS_NUM', 'Number of Related Links');
define('L_CONF_RELATEDLINKS_NUM_DESC', 'How many related links do you want to use?');
define('L_CONF_TIMEFORMAT', 'Time format');
define('L_CONF_TIMEFORMAT_DESC', 'How to format the time? (<a href="./?page=other&subpage=help#configuration.dateformat">Help</a>)');
define('L_CONF_EDITFAILED', 'Editing configuration failed!');

/* Menu */
define('L_MENU_TEMPLATES', 'Templates');
define('L_MENU_USERS', 'Users');
define('L_MENU_PERMISSIONS', 'Permissions');
define('L_MENU_CONFIG', 'Configuration');
define('L_MENU_CATEGORIES', 'Categories');
define('L_MENU_NEWS', 'News');
define('L_MENU_OTHER', 'Other');
define('L_MENU_EXTERN', 'External Page');
define('L_MENU_ADDTEMPLATE', 'Add template');
define('L_MENU_SHOWTEMPLATES', 'Show templates');
define('L_MENU_ADDUSER', 'Add user');
define('L_MENU_SHOWUSER', 'Show users');
define('L_MENU_SEARCHUSER', 'Search user');
define('L_MENU_ADDPERMISSIONS', 'Add permissions');
define('L_MENU_SHOWPERMISSIONS', 'Show permissions');
define('L_MENU_EDITCONFIG', 'Edit configuration');
define('L_MENU_ADDCAT', 'Add category');
define('L_MENU_SHOWCATS', 'Show categories');
define('L_MENU_CATSDEACTIVATED', 'Categories are deactivated');
define('L_MENU_ADDNEWS', 'Add news');
define('L_MENU_SHOWNEWS', 'Show news');
define('L_MENU_SEARCHNEWS', 'Search news');
define('L_MENU_HELP', 'Help');
define('L_MENU_LICENSE', 'License');
define('L_MENU_PSHP', 'PowerScripts Homepage');
define('L_MENU_CHOOSESECTION', 'Please choose a section');

/* Users */
define('L_USR_NICKNAME', 'Nickname');
define('L_USR_PASSWORD', 'Password');
define('L_USR_COOKIESMUSTBEENABLED', '<u>Attention:</u> To login successfully, you have to enable cookies!');
define('L_USR_LOGINOK', 'You logged in successfully');
define('L_USR_WRONGPW', 'The chosen password is not correct!');
define('L_USR_NOUSR', 'The chosen user does not exist in the database!');
define('L_USR_NICKANDPW', 'You have to fill in nickname and password!');
define('L_USR_NICKANDEMAIL', 'You have to fill in nickname and E-Mail address!');
define('L_USR_USRADDED', 'User was added successfully!');
define('L_USR_ADDUSR', 'Add user');
define('L_USR_NICKNAME_DESC', 'Nickname for the new user');
define('L_USR_EMAIL', 'E-Mail');
define('L_USR_EMAIL_DESC', 'The E-Mail address for the new user');
define('L_USR_SHOWEMAIL', 'Show E-Mail');
define('L_USR_SHOWEMAIL_DESC', 'Show the E-Mail address of the new user?');
define('L_USR_SENDMAIL', 'Send E-Mail');
define('L_USR_SENDMAIL_DESC', 'Send E-Mail with login data to the new user?');
define('L_USR_USREDITED', 'User was edited successfully');
define('L_USR_EDITUSR', 'Edit user');
define('L_USR_CHOOSEUSER', 'Please choose a user');
define('L_USR_NEWPW', 'New password');
define('L_USR_NEWPW_DESC', 'Generate new password for the user?');
define('L_USR_STATUS', 'Status');
define('L_USR_STATUS_DESC', 'Status of the user');
define('L_USR_ADMIN', 'Admin');
define('L_USR_SHOWUSR', 'Show user');
define('L_USR_SHOWUSR_DESC', 'Click on a nickname to edit the user. 25 users shown per page!');
define('L_USR_SEARCHSTRINGNEEDED', 'Insert a search string');
define('L_USR_SEARCHUSR', 'Search user');
define('L_USR_SEARCHFIELD', 'Search field');
define('L_USR_SEARCHFIELD_DESC', 'Choose the field to search in');
define('L_USR_USRID', 'User-ID');
define('L_USR_SEARCHSTRING', 'Search string');
define('L_USR_SEARCHSTRING_DESC', 'What do you want to search?');
define('L_USR_PROFILEEDITED', 'Your profile was edited!');
define('L_USR_NICKNAME_DESC_PROF', 'Your nickname');
define('L_USR_EMAIL_DESC_PROF', 'Your E-Mail address');
define('L_USR_SHOWEMAIL_DESC_PROF', 'Show your E-Mail address?');
define('L_USR_PASSWORD_DESC_PROF', 'Your password (with confirmation)');
define('L_USR_PROFILE_DESC', 'After changing your password you have to log in again!');
define('L_USR_EDITPROFILE', 'Edit profile');
define('L_USR_NOADMIN', 'The chosen user has no access to the administration area!');
define('L_USR_PLEASELOGIN', 'Please log in');
define('L_USR_LOGIN', 'Login');
define('L_USR_LOGOUT', 'Logout');
define('L_USR_WRONGEMAIL', 'The chosen E-Mail address is invalid!');
define('L_USR_USRALREADYEXISTS', 'User with this nickname or E-Mail address already exists!');
define('L_USR_NOUSRINDB', 'No users in the database');
define('L_USR_INSERTNICKNAMEANDEMAIL', 'You have to insert nickname and E-Mail!');
define('L_USR_NOUSRFOUND', 'No users found!');
define('L_USR_PWNOTCONFIRMED', 'Password confirmation failed!');
define('L_USR_HELLO', 'Hello');

/* Templates */
define('L_TEMPL_ADDTEMPLATE', 'Add template');
define('L_TEMPL_SHOWTEMPLATES', 'Show templates');
define('L_TEMPL_TITLE', 'Title');
define('L_TEMPL_TITLE_DESC', 'The title of the template');
define('L_TEMPL_EDITTEMPLATE', 'Edit template');
define('L_TEMPL_GENERAL', 'G E N E R A L');
define('L_TEMPL_OUTPUT', 'O U T P U T');
define('L_TEMPL_INPUT', 'F O R M S / I N P U T');
define('L_TEMPL_EMAILS', 'E - M A I L S');
define('L_TEMPL_DELETE', 'Delete');
define('L_TEMPL_DELETE_DESC', 'Should the template be deleted?');
define('L_TEMPL_MESSAGE', 'Message');
define('L_TEMPL_MESSAGE_DESC', 'Output of the default message (e.g. errors)');
define('L_TEMPL_HEADLINES', 'Headlines');
define('L_TEMPL_HEADLINES_DESC', 'Output of the headlines');
define('L_TEMPL_NEWS', 'News');
define('L_TEMPL_NEWS_DESC', 'Output of the news');
define('L_TEMPL_COMMENTS', 'Comments');
define('L_TEMPL_COMMENTS_DESC', 'Output of the comments');
define('L_TEMPL_USERMENU', 'User menu');
define('L_TEMPL_USERMENU1_DESC', 'Output of the user menu (not logged in)');
define('L_TEMPL_USERMENU2_DESC', 'Output of the user menu (logged in)');
define('L_TEMPL_COMMENTFORM', 'Comment form');
define('L_TEMPL_COMMENTFORM_DESC', 'Form for entering comments');
define('L_TEMPL_FORMTARGET', 'Form target');
define('L_TEMPL_REGISTERFORM', 'Registration form');
define('L_TEMPL_REGISTERFORM_DESC', 'Form for user registration');
define('L_TEMPL_LOGINFORM', 'Login form');
define('L_TEMPL_LOGINFORM_DESC', 'Form for the user login');
define('L_TEMPL_LOGOUTFORM', 'Logout confirmation');
define('L_TEMPL_LOGOUTFORM_DESC', 'Confirmation page for the logout');
define('L_TEMPL_LINKTARGET', 'Link target:');
define('L_TEMPL_SENDDATAFORM', 'Send-data form');
define('L_TEMPL_SENDDATAFORM_DESC', 'Form for resending login data');
define('L_TEMPL_PROFILEFORM', 'Profile form');
define('L_TEMPL_PROFILEFORM_DESC', 'Form for editing the user profile');
define('L_TEMPL_ARCHIVEFORM', 'Archive form');
define('L_TEMPL_ARCHIVEFORM_DESC', '');
define('L_TEMPL_SENDNEWSFORM', 'Send-news form');
define('L_TEMPL_SENDNEWSFORM_DESC', 'Form for submitting news');
define('L_TEMPL_USERADDEDMAIL', 'User added - E-Mail');
define('L_TEMPL_USERADDEDMAIL_DESC', 'E-Mail when a user was added by an admin');
define('L_TEMPL_USEREDITEDMAIL', 'User edited - E-Mail');
define('L_TEMPL_USEREDITEDMAIL_DESC', 'E-Mail when a user was edited by an admin');
define('L_TEMPL_USERREGISTEREDMAIL', 'User registered - E-Mail');
define('L_TEMPL_USERREGISTEREDMAIL_DESC', 'E-Mail when a user has registered');
define('L_TEMPL_DATAMAIL', 'User data - E-Mail');
define('L_TEMPL_DATAMAIL_DESC', 'E-Mail when a user requests their login data');
define('L_TEMPL_CHOOSETEMPLATE', 'You have to choose a template');
define('L_TEMPL_SHOW_DESC', 'Click on a template to edit it.');
define('L_TEMPL_TITLENEEDED', 'You have to enter a title for the new template!');
define('L_TEMPL_TEMPLATEADDED', 'The template was added successfully!');
define('L_TEMPL_TEMPLATEALREADYEXISTS', 'A template with this title already exists');
define('L_TEMPL_NOSTANDARDTEMPLATE', 'The default template does not exist!');
define('L_TEMPL_NOTEMPLATES', 'No templates available');
define('L_TEMPL_INSERTALL', 'You have to fill in all fields!');
define('L_TEMPL_NOSTANDARDEDIT', 'The default template may not be edited/deleted!');
define('L_TEMPL_TEMPLATEEDITED', 'The template was edited successfully!');
define('L_TEMPL_RIGHTTEMPLATENEEDED', 'A valid template must be chosen!');
define('L_TEMPL_TEMPLATEDELETED', 'The template was deleted successfully!');
define('L_TEMPL_RELATEDLINKS', 'Related Links');
define('L_TEMPL_RELATEDLINKS_DESC', 'The output for a single related link');

/* Permissions */
define('L_PERM_ADDPERMISSIONS', 'Add permissions');
define('L_PERM_SHOWPERMISSIONS', 'Show permissions');
define('L_PERM_INSERTNICK', 'You have to enter a nickname!');
define('L_PERM_PERMISSIONADDED', 'Permissions were added successfully!');
define('L_PERM_NICK', 'Nickname');
define('L_PERM_NICK_DESC', 'The correct nickname of the user');
define('L_PERM_PERMISSIONS', 'Permissions');
define('L_PERM_PERMISSIONS_DESC', 'Which permissions should the user have?');
define('L_PERM_READ', 'Read');
define('L_PERM_WRITE', 'Write');
define('L_PERM_TEMPLATES', 'Templates');
define('L_PERM_CONFIG', 'Configuration');
define('L_PERM_USER', 'Users');
define('L_PERM_CATS', 'Categories');
define('L_PERM_NEWS', 'News');
define('L_PERM_COMMENTS', 'Comments');
define('L_PERM_CHOOSEADMIN', 'You have to choose an admin!');
define('L_PERM_PERMISSIONSDELETED', 'The permissions were deleted successfully!');
define('L_PERM_PERMISSIONSEDITED', 'The permissions were edited successfully!');
define('L_PERM_EDITPERMISSIONS', 'Edit permissions');
define('L_PERM_DELETE', 'Delete');
define('L_PERM_DELETE_DESC', "Should the user's permissions be deleted?");
define('L_PERM_SHOW_DESC', 'Click on a nickname to edit the permissions.');
define('L_PERM_CANTWRITETODB', 'The permissions could not be written to the database!');
define('L_PERM_ALREADYADMIN', 'The given user is already an admin!');
define('L_PERM_USERNOTEXISTING', 'No user with the given nickname exists or the user is deactivated!');
define('L_PERM_NOPERMISSIONS', 'No permissions available');
define('L_PERM_SECTION', 'Section');
define('L_PERM_PERMISSIONSNOTDELETED', 'The permissions could not be deleted!');
define('L_PERM_NOADMIN', 'The chosen user is not an admin!');
define('L_PERM_CANNOTWRITETODB', '');

/* News */
define('L_NEWS_WRITENEWS', 'Write news');
define('L_NEWS_SHOWNEWS', 'Show news');
define('L_NEWS_EDITNEWS', 'Edit news');
define('L_NEWS_SEARCHNEWS', 'Search news');
define('L_NEWS_TITLEANDTEXTNEEDED', 'A news title and news text must be provided');
define('L_NEWS_ALSOCATEGORY', 'as well as a chosen category');
define('L_NEWS_NEWSADDED', 'News was added successfully!');
define('L_NEWS_CATEGORY', 'Category');
define('L_NEWS_CATEGORY_DESC', 'The category under which the news entry should appear');
define('L_NEWS_TITLE', 'Title');
define('L_NEWS_TITLE_DESC', 'The title of the news entry');
define('L_NEWS_TEXT', 'Text');
define('L_NEWS_TEXT_DESC', 'The text of the news entry');
define('L_NEWS_ON', 'on');
define('L_NEWS_OFF', 'off');
define('L_NEWS_LONGTEXT', 'Long text');
define('L_NEWS_LONGTEXT_DESC', 'The long text of the news entry');
define('L_NEWS_COMMENTSEDITED', 'The comments were edited successfully!');
define('L_NEWS_NEWSDELETED', 'The news entry was deleted successfully!');
define('L_NEWS_NEWSEDITED', 'The news entry was edited successfully!');
define('L_NEWS_DELETE', 'Delete');
define('L_NEWS_DELETE_DESC', 'Should the news entry and all possibly existing comments be deleted?');
define('L_NEWS_STATUS', 'Status');
define('L_NEWS_STATUS_DESC', 'The status of the news entry');
define('L_NEWS_EDITCOMMENTS', 'Edit comments');
define('L_NEWS_CHOOSENEWS', 'You have to choose a valid news entry!');
define('L_NEWS_DATE', 'Date');
define('L_NEWS_SHOW_DESC', 'Click on a news title to edit the news.');
define('L_NEWS_SEARCHFIELD', 'Search field');
define('L_NEWS_SEARCHFIELD_DESC', 'Which field should be searched?');
define('L_NEWS_NEWSID', 'News ID');
define('L_NEWS_SEARCHSTRING', 'Search term');
define('L_NEWS_SEARCHSTRING_DESC', 'What do you want to search for?');
define('L_NEWS_SEARCHSTRINGNEEDED', 'A search term must be provided!');
define('L_NEWS_CHOOSECAT', 'Choose category');
define('L_NEWS_NOCATSAVAILABLE', 'No categories available yet!');
define('L_NEWS_ADDINGFAILED', 'The news could not be added!');
define('L_NEWS_BADCAT', 'Invalid category');
define('L_NEWS_NONEWS', 'No news in the database');
define('L_NEWS_NOCOMMENTS', 'No comments in the database');
define('L_NEWS_DELETECOMMENT', 'Delete');
define('L_NEWS_DELETECOMMENT_DESC', 'Should the comment be deleted?');
define('L_NEWS_INFO', 'Info');
define('L_NEWS_INFO_DESC', 'Some information');
define('L_NEWS_WRITTENBY', 'Written by');
define('L_NEWS_GUEST', 'Guest');
define('L_NEWS_ONDATE', 'on');
define('L_NEWS_AT', 'at');
define('L_NEWS_COMMENTEXT_DESC', 'The comment text');
define('L_NEWS_ONECOMMENTWRONG', 'One of the comments is invalid!');
define('L_NEWS_COMMENTEDITERROR', 'An error occurred while editing the comments!');
define('L_NEWS_NEWSNOTDELETED', 'The news entry could not be deleted!');
define('L_NEWS_NEWSNOTEDITED', 'The news entry could not be edited!');
define('L_NEWS_RELATEDLINKS', 'Related Links');
define('L_NEWS_RELATEDLINKS_DESC', 'Here you can post related links for this news entry');
define('L_NEWS_RL_TITLE', 'Title');
define('L_NEWS_RL_URL', 'URL/Path');
define('L_NEWS_RL_TARGET', 'Target');
define('L_NEWS_TIME', 'Publication date');
define('L_NEWS_TIME_DESC', 'When should the news appear on the homepage?');
define('L_NEWS_DAY', 'Day');
define('L_NEWS_MONTH', 'Month');
define('L_NEWS_YEAR', 'Year');
define('L_NEWS_HOUR', 'Hour');
define('L_NEWS_MIN', 'Minute');
define('L_NEWS_JANUARY', 'January');
define('L_NEWS_FEBRUARY', 'February');
define('L_NEWS_MARCH', 'March');
define('L_NEWS_APRIL', 'April');
define('L_NEWS_MAY', 'May');
define('L_NEWS_JUNE', 'June');
define('L_NEWS_JULY', 'July');
define('L_NEWS_AUGUST', 'August');
define('L_NEWS_SEPTEMBER', 'September');
define('L_NEWS_OCTOBER', 'October');
define('L_NEWS_NOVEMBER', 'November');
define('L_NEWS_DECEMBER', 'December');
define('L_NEWS_NOCOMMENTTEXT', 'One or more comments are missing the text!');

/* Other */
define('L_OTHER_HELP', 'Help');
define('L_OTHER_LICENSE', 'License');
define('L_OTHER_LICENSE_DESC', 'PowerNews is distributed under the terms of the <b>General Public License</b>. The latest version of the license can be found on the <a href="http://www.powerscripts.org" target="_blank">PowerScripts Homepage</a>.');
define('L_OTHER_NOLOCALLICENSE', 'Access to the local copy of the license failed. The file may have been deleted.');

/* Titles */
define('L_TITLE_LOGIN', 'L O G I N');
define('L_TITLE_MAIN', 'W E L C O M E');
define('L_TITLE_NEWS', 'N E W S');
define('L_TITLE_PERMISSIONS', 'P E R M I S S I O N S');
define('L_TITLE_TEMPLATES', 'T E M P L A T E S');
define('L_TITLE_OTHER', 'O T H E R');
define('L_TITLE_PROFILE', 'P R O F I L E');
define('L_TITLE_USERS', 'U S E R S');
define('L_TITLE_DOCUMENTNOTFOUND', '4 0 4&nbsp;&nbsp;-&nbsp;&nbsp;D O C U M E N T&nbsp;&nbsp;N O T&nbsp;&nbsp;F O U N D');

/* E-Mails */
define('L_EMAIL_SUBJECT', 'PowerNews Auto Notification');
define('L_EMAIL_AUTHOR', 'PowerScripts Automailer');

/* Something else */
define('L_ALL_NOPAGE', 'The requested page does not exist!');
define('L_ALL_SUBPAGENOTFOUND', 'The requested subpage was not found!');
define('L_ALL_CHOOSESUBPAGE', 'Please choose a subpage');
define('L_ALL_ACTIVATED', 'Activated');
define('L_ALL_DEACTIVATED', 'Deactivated');
define('L_ALL_UNCHECKED', 'Unchecked');
define('L_ALL_ACCESSDENIED', 'Access denied!');
define('L_ALL_RESETDATA', 'Reset data');
define('L_ALL_YES', 'Yes');
define('L_ALL_NO', 'No');
define('L_ALL_NOPAGES', 'No pages');
define('L_QUICKLINKS', 'Quicklinks');
define('L_ALL_PAGECREATEDIN', 'Page created in');
define('L_ALL_SECONDSBY', 'seconds by');
define('L_ALL_WELCOME', 'Welcome to the PowerNews Admincenter<br><br>Please send any bugs you find to <a href="mailto:bugs@powerscripts.org?subject=PowerNews Bug">bugs@powerscripts.org</a>!');
