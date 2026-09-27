# ====================================================================
#  PowerNews – Entwicklungsdaten für den Docker-Stack
# ====================================================================
#  NUR für die lokale Entwicklung. .docker/docker-compose.yml spielt die
#  Datei nach powernews.sql ein – einmalig, beim ersten Start mit leerem
#  Datenbank-Volume. Sie gehört nicht zum Release-Archiv (.docker/ ist in
#  .gitattributes als export-ignore markiert).
#
#  Administrator im Adminbereich (http://localhost:8087/pnadmin/):
#    Nickname: admin
#    Passwort: powernews-dev
#
#  Seiten-URL und Absender passen zum Stack (Mails landen in Mailpit,
#  http://localhost:8033/).
# ====================================================================

SET NAMES utf8mb4;

UPDATE `pn_config` SET `url` = 'http://localhost:8087', `email` = 'noreply@powernews.local';

INSERT INTO `pn_users` (`id`, `nickname`, `email`, `password`, `registered`, `showemail`, `status`)
VALUES (1, 'admin', 'admin@powernews.local', '$2y$12$Twej4sdRMuPMb/bOuHUAQ.1zCzx2dNdBsONvhh70Lvo1wQyE9ENoS', UNIX_TIMESTAMP(), 'NO', 'Activated');

INSERT INTO `pn_permissions` (`userid`, `canreadtemplates`, `canwritetemplates`, `canreadconfig`, `canwriteconfig`,
    `canreadusers`, `canwriteusers`, `canreadpermissions`, `canwritepermissions`, `canreadcategories`,
    `canwritecategories`, `canreadnews`, `canwritenews`, `canreadcomments`, `canwritecomments`)
VALUES (1, 'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES');
