
--
-- Mise à jour de la version 0.5.1 à 0.5.2
--

insert into llx_const(name, value, type, note, visible) values ('ADH_TEXT_NEW_ADH','','texte','Texte d\'entete du formulaire d\'adhesion en ligne',0);
insert into llx_const(name, value, type, note, visible) values ('ADH_CARD_HEADER_TEXT','%ANNEE%','string','Texte imprime sur le haut de la carte adherent',0);
insert into llx_const(name, value, type, note, visible) values ('ADH_CARD_FOOTER_TEXT','Association FreeLUG http://www.freelug.org/','string','Texte imprime sur le bas de la carte adherent',0);
insert into llx_const(name, value, type, note, visible) values ('ADH_CARD_TEXT','%TYPE% n° %ID%\r\n%PRENOM% %NOM%\r\n<%EMAIL%>\r\n%ADRESSE%\r\n%CP% %VILLE%\r\n%PAYS%','texte','Texte imprime sur la carte adherent',0);
insert into llx_const(name, value, type, note, visible) values ('MAIN_MAILMAN_ADMINPW','','string','Mot de passe Admin des liste mailman',0);
insert into llx_const(name, value, type, note, visible) values ('MAIN_MAILMAN_SERVER','lists.ipsyn.net','string','Serveur hebergeant les interfaces d\'Admin des listes mailman',0);
replace into llx_const(name, value, type, note, visible) values ('MAIN_MAILMAN_UNSUB_URL','http://%SERVER%/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%ADMINPW%&user=%EMAIL%','chaine','Url de desinscription aux listes mailman',0);
replace into llx_const(name, value, type, note, visible) values ('MAIN_MAILMAN_URL','http://%SERVER%/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%ADMINPW%&send_welcome_msg_to_this_batch=1&subscribees=%EMAIL%','chaine','url pour les inscriptions mailman',0);
insert into llx_const(name, value, type, note, visible) values ('MAIN_MAILMAN_LISTS_COTISANT','','string','Liste(s) auxquelles les nouveaux cotisants sont inscris automatiquement',0);
UPDATE llx_const SET name='ADHERENT_GLASNOST_PASS' WHERE name='MAIN_GLASNOST_PASS';
UPDATE llx_const SET name='ADHERENT_GLASNOST_SERVEUR' WHERE name='MAIN_GLASNOST_SERVEUR';
UPDATE llx_const SET name='ADHERENT_GLASNOST_USER' WHERE name='MAIN_GLASNOST_USER';
UPDATE llx_const SET name='ADHERENT_MAILMAN_ADMINPW' WHERE name='MAIN_MAILMAN_ADMINPW';
UPDATE llx_const SET name='ADHERENT_MAILMAN_LISTS' WHERE name='MAIN_MAILMAN_LISTS';
UPDATE llx_const SET name='ADHERENT_MAILMAN_LISTS_COTISANT' WHERE name='MAIN_MAILMAN_LISTS_COTISANT';
UPDATE llx_const SET name='ADHERENT_MAILMAN_SERVER' WHERE name='MAIN_MAILMAN_SERVER';
UPDATE llx_const SET name='ADHERENT_MAILMAN_UNSUB_URL' WHERE name='MAIN_MAILMAN_UNSUB_URL';
UPDATE llx_const SET name='ADHERENT_MAILMAN_URL' WHERE name='MAIN_MAILMAN_URL';
UPDATE llx_const SET name='ADHERENT_MAIL_COTIS' WHERE name='MAIN_MAIL_COTIS';
UPDATE llx_const SET name='ADHERENT_MAIL_COTIS_SUBJECT' WHERE name='MAIN_MAIL_COTIS_SUBJECT';
UPDATE llx_const SET name='ADHERENT_MAIL_EDIT' WHERE name='MAIN_MAIL_EDIT';
UPDATE llx_const SET name='ADHERENT_MAIL_EDIT_SUBJECT' WHERE name='MAIN_MAIL_EDIT_SUBJECT';
UPDATE llx_const SET name='ADHERENT_MAIL_FROM' WHERE name='MAIN_MAIL_FROM';
UPDATE llx_const SET name='ADHERENT_MAIL_NEW' WHERE name='MAIN_MAIL_NEW';
UPDATE llx_const SET name='ADHERENT_MAIL_NEW_SUBJECT' WHERE name='MAIN_MAIL_NEW_SUBJECT';
UPDATE llx_const SET name='ADHERENT_MAIL_RESIL' WHERE name='MAIN_MAIL_RESIL';
UPDATE llx_const SET name='ADHERENT_MAIL_RESIL_SUBJECT' WHERE name='MAIN_MAIL_RESIL_SUBJECT';
UPDATE llx_const SET name='ADHERENT_MAIL_VALID' WHERE name='MAIN_MAIL_VALID';
UPDATE llx_const SET name='ADHERENT_MAIL_VALID_SUBJECT' WHERE name='MAIN_MAIL_VALID_SUBJECT';
UPDATE llx_const SET name='ADHERENT_SPIP_DB' WHERE name='MAIN_SPIP_DB';
UPDATE llx_const SET name='ADHERENT_SPIP_PASS' WHERE name='MAIN_SPIP_PASS';
UPDATE llx_const SET name='ADHERENT_SPIP_SERVEUR' WHERE name='MAIN_SPIP_SERVEUR';
UPDATE llx_const SET name='ADHERENT_SPIP_USER' WHERE name='MAIN_SPIP_USER';
UPDATE llx_const SET name='ADHERENT_USE_GLASNOST' WHERE name='MAIN_USE_GLASNOST';
UPDATE llx_const SET name='ADHERENT_USE_GLASNOST_AUTO' WHERE name='MAIN_USE_GLASNOST_AUTO';
UPDATE llx_const SET name='ADHERENT_USE_MAILMAN' WHERE name='MAIN_USE_MAILMAN';
UPDATE llx_const SET name='ADHERENT_USE_SPIP' WHERE name='MAIN_USE_SPIP';
UPDATE llx_const SET name='ADHERENT_USE_SPIP_AUTO' WHERE name='MAIN_USE_SPIP_AUTO';
UPDATE llx_const SET name='ADHERENT_CARD_FOOTER_TEXT' WHERE name='ADH_CARD_FOOTER_TEXT';
UPDATE llx_const SET name='ADHERENT_CARD_HEADER_TEXT' WHERE name='ADH_CARD_HEADER_TEXT';
UPDATE llx_const SET name='ADHERENT_CARD_TEXT' WHERE name='ADH_CARD_TEXT';
UPDATE llx_const SET name='ADHERENT_TEXT_NEW_ADH' WHERE name='ADH_TEXT_NEW_ADH';
insert into llx_const(name, value, type, note, visible) values ('MAIN_MAIL_FROM','mail@domain.com','string','Adresse expediteur des mails',0);






