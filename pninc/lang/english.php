<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

/* This is the extern language file - you can edit all outputs from here */

/* English language file written by PowerScripts (Stefan Kraemer) */

/* Users */
define('L_USR_WRONGEMAIL', 'Your e-mail address seems to be incorrect!');
define('L_USR_USRALREADYEXISTS', 'This username or email already exists!');
define('L_USR_REGISTERED', 'You registered successfully and can now log in with your nickname and password.');
define('L_USR_LOGGEDIN', 'You logged in successfully!');
define('L_USR_WRONGPASSWORD', 'Your password is incorrect!');
define('L_USR_NOUSR', 'No existing user with this nickname!');
define('L_USR_NOUSRREGISTERED', 'No existing user with this nickname or this email!');
define('L_USR_TOOMANYSEARCHRESULTS', 'Too many users found. Please specify your request!');
define('L_USR_DATASENT', 'Userdata successfully mailed!');
define('L_USR_CANTSENDMAIL', 'Problem with sending your userdata, please retry!');
define('L_USR_NICKNAMEOREMAILALREADYUSED', 'The chosen nickname or e-mail address is already used by another user!');
define('L_USR_PASSNOTEQUAL', 'The passwords do not match!');
define('L_USR_NOTLOGGEDIN', 'You are not logged in!');
define('L_USR_CANNOTLOGOUT', 'You can not log out, if you are not logged in!');
define('L_USR_PROFILEEDITED', 'Your profile was edited. If you changed your password, you have to log in again!');
define('L_USR_ALREADYLOGGEDIN', 'You are already logged in.');
define('L_USR_RESETMAIL_BODY', "Hello {NICKNAME},\n\na new password was requested for your account at {SITE}. Use the following link within {VALIDMINUTES} minutes to choose a new password:\n\n{RESETLINK}\n\nIf you did not request a new password, simply ignore this e-mail. Your current password stays valid.\n\nPlease do not reply to this automatically generated e-mail!");
define('L_USR_REGISTERMAIL_BODY', "Hello {NICKNAME},\n\nwelcome to {SITE}! Your registration is complete. You can log in right away with your nickname \"{NICKNAME}\" and the password you chose during registration:\n\n{LOGINLINK}\n\nPlease do not reply to this automatically generated e-mail!");
define('L_USR_RESETTITLE', 'Choose a new password');
define('L_USR_RESETINTRO', 'Hello %s, please enter your new password twice. It must be at least 8 characters long.');
define('L_USR_NEWPASSWORD', 'New password');
define('L_USR_REPEATNEWPASSWORD', 'Repeat new password');
define('L_USR_SAVEPASSWORD', 'Save password');
define('L_USR_RESETINVALID', 'The link to choose a new password is invalid or has expired. Please request a new link.');
define('L_USR_PASSWORDRESET', 'Your new password has been saved. You can now log in with it.');
define('L_USR_INVITETITLE', 'Choose the password for your account');
define('L_USR_INVITEINTRO', 'Welcome, %1$s! An account has been set up for you. Choose your password now and enter it a second time to confirm. Afterwards you log in with the nickname "%1$s" and this password.');
define('L_USR_INVITEDONE', 'Your password has been saved and your account is ready. You can now log in with your nickname and the new password.');
define('L_USR_INVALIDREGISTRATION', 'Invalid input. The nickname must be 3 to 30 characters long (letters, digits, dot, underscore, hyphen) and the e-mail address must be valid.');
define('L_USR_REGISTRATIONFAILED', 'Registration failed. Please try again later.');
define('L_USR_TOOMANYATTEMPTS', 'Too many failed attempts. Please try again in 15 minutes.');
define('L_USR_LOGINFAILED', 'Nickname or password is incorrect.');
define('L_USR_DATAREQUESTSENT', 'If an account with these details exists, an e-mail with a link to choose a new password has been sent. The link is valid for 60 minutes.');
define('L_USR_TOOMANYREQUESTS', 'Too many requests. Please try again later.');
define('L_USR_INVALIDPROFILE', 'Invalid input. Please check nickname, e-mail address and homepage.');
define('L_USR_PASSWORDTOOSHORT', 'The password must be at least 8 characters long.');
define('L_USR_PASSWORDTOOLONG', 'The password must not be longer than 72 characters (umlauts and special characters count double).');
define('L_USR_PASSWORDLABEL', 'Password');
define('L_USR_PASSWORDREPEATLABEL', 'Repeat password');
define('L_USR_PASSWORDHINT', 'At least 8 characters.');

/* News */
define('L_NEWS_NONEWS', 'No news');
define('L_NEWS_CHOOSENEWS', 'You have to choose an existing news entry!');
define('L_NEWS_NOCOMMENTS', 'No comments');
define('L_NEWS_UNKNOWN', 'Unknown');
define('L_NEWS_GUEST', 'Guest');
define('L_NEWS_CATSDEACTIVATED', 'Categories are deactivated');
define('L_NEWS_WRONGCAT', 'Invalid category');
define('L_NEWS_NOHEADLINES', 'No headlines');
define('L_NEWS_CANNOTPOSTCOMMENTS', 'You are not allowed to post comments unless you are registered and logged in!');
define('L_NEWS_COMMENTPOSTED', 'Your comment was posted successfully!');
define('L_NEWS_HOURS', 'Hours');
define('L_NEWS_MINUTES', 'Minutes');
define('L_NEWS_SECONDS', 'Seconds');
define('L_NEWS_TIMEBETWEEN2COMMENTS', 'There must be a pause between two comments!');
define('L_NEWS_NONEWSFOUND', 'No news found!');
define('L_NEWS_NOCATS', 'No categories!');
define('L_NEWS_NOCATS_ERROR', 'Error: No categories available! Please contact the administrator.');
define('L_NEWS_NOCATS_CANNOT_SEND', 'News cannot be submitted at this time because no categories are available. Please try again later.');
define('L_NEWS_SELECTCAT_ERROR', 'Please select a category!');
define('L_NEWS_CHOOSECAT', 'Choose category');
define('L_NEWS_NEWSSENTIN', 'Your news were sent in!');
define('L_NEWS_CANNOTSENDNEWS', 'You have to be registered and logged in to send news!');
define('L_NEWS_NONEWSSENDIN', 'News-sending deactivated!');
define('L_NEWS_MORE', 'more');
define('L_NEWS_RL_TITLE', 'Title');
define('L_NEWS_RL_URL', 'URL');
define('L_NEWS_RL_TARGET', 'Target');
define('L_NEWS_RELATEDLINKS', 'Related links');
define('L_NEWS_NONEWSINMONTH', 'No news were published in this month.');
define('L_NEWS_COMMENTTOOLONG', 'The comment is too long (at most %d characters).');
define('L_NEWS_NEWSNOTFOUND', 'The news entry was not found.');

/* E-Mail */
define('L_EMAIL_SUBJECT_REGISTER', 'Welcome to %s');
define('L_EMAIL_SUBJECT_RESET', 'New password for %s');
define('L_EMAIL_AUTHOR', 'PowerNews');

/* Templates */
define('L_TEMPL_CANNOTLOADTEMPL', 'Unable to load template!');
define('L_TEMPL_JANUARY', 'January');
define('L_TEMPL_FEBRUARY', 'February');
define('L_TEMPL_MARCH', 'March');
define('L_TEMPL_APRIL', 'April');
define('L_TEMPL_MAY', 'May');
define('L_TEMPL_JUNE', 'June');
define('L_TEMPL_JULY', 'July');
define('L_TEMPL_AUGUST', 'August');
define('L_TEMPL_SEPTEMBER', 'September');
define('L_TEMPL_OCTOBER', 'October');
define('L_TEMPL_NOVEMBER', 'November');
define('L_TEMPL_DECEMBER', 'December');

/* Other */
define('L_ALL_FILLALL', 'Please fill in all fields!');
define('L_ALL_CSRFINVALID', 'The form has expired or is invalid. Please reload the page and try again.');
define('L_ALL_CONFIGROWS', 'The table %s must contain exactly one PowerNews configuration. Please check the database.');
define('L_MSG_SUCCESS', 'Done');
define('L_MSG_DANGER', 'Error');
define('L_MSG_WARNING', 'Notice');
define('L_MSG_INFO', 'Information');
define('L_MSG_CONTINUE', 'Continue');
