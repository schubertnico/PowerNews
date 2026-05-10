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

/* This is the extern language file - you can edit all outputs from here */

/* German language file written by PowerScripts (Stefan Kraemer) */

/* Users */
define('L_USR_WRONGEMAIL', 'Your E-Mail adress seems to be incorrect!');
define('L_USR_USRALREADYEXISTS', 'This username or email already exists!');
define('L_USR_REGISTERED', 'You registered successfully. A mail with your userdata is on the way.');
define('L_USR_LOGGEDIN', 'You logged in successfully!');
define('L_USR_WRONGPASSWORD', 'Your password is incorrect!');
define('L_USR_NOUSR', 'No existing user with this nickname!');
define('L_USR_NOUSRREGISTERED', 'No existing user with this nickname or this email!');
define('L_USR_TOOMANYSEARCHRESULTS', 'Too many users found. Please specify your request!');
define('L_USR_DATASENT', 'Userdata successfully mailed!');
define('L_USR_CANTSENDMAIL', 'Problem with sending your userdata, please retry!');
define('L_USR_NICKNAMEOREMAILALREADYUSED', 'The chosen nickname or the chosen E-Mail adress are already used by another user!');
define('L_USR_PASSNOTEQUAL', 'Passwords are not equal!');
define('L_USR_NOTLOGGEDIN', 'You are not logged in!');
define('L_USR_CANNOTLOGOUT', 'You can not log out, if you are not logged in!');
define('L_USR_PROFILEEDITED', 'Your profile was edited. If you changed your password, you have to log in again!');

/* News */
define('L_NEWS_NONEWS', 'No news');
define('L_NEWS_CHOOSENEWS', 'You have to choose an existing news entry!');
define('L_NEWS_NOCOMMENTS', 'No comments');
define('L_NEWS_UNKNOWN', 'Unknown');
define('L_NEWS_GUEST', 'Guest');
define('L_NEWS_CATSDEACTIVATED', 'Categories are deactivated');
define('L_NEWS_WRONGCAT', 'Invalid category');
define('L_NEWS_NOHEADLINES', 'No headlines');
define('L_NEWS_CANNOTPOSTCOMMENTS', 'You are not allowed to post comments if you are not registed and logged in!');
define('L_NEWS_COMMENTPOSTED', 'You comment was posted successfully!');
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

/* E-Mail */
define('L_EMAIL_TITLE', 'PowerNews Automailer');
define('L_EMAIL_AUTHOR', 'PowerNews Automailer');

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
