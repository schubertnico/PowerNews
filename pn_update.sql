ALTER TABLE pn_config CHANGE commentwriting commentwriting ENUM('Guests/Registered', 'Registered') DEFAULT 'Guests/Registered' NOT NULL;
ALTER TABLE pn_config CHANGE newssending newssending ENUM('Guests/Registered', 'Registered') DEFAULT 'Guests/Registered' NOT NULL;
ALTER TABLE pn_config CHANGE smilies smilies ENUM('NO', 'Comments', 'Comments/News', 'News') DEFAULT 'NO' NOT NULL;
ALTER TABLE pn_config CHANGE bbcode bbcode ENUM('NO', 'Comments', 'Comments/News', 'News') DEFAULT 'NO' NOT NULL;
ALTER TABLE pn_config CHANGE html html ENUM('NO', 'Comments', 'Comments/News', 'News') DEFAULT 'NO' NOT NULL;
