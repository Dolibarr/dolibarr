
--
-- Mise à jour de la version 0.5.1 à 0.5.2
--

insert into llx_const(name, value, type, note, visible) values ('ADH_TEXT_NEW_ADH','','texte','Texte d\'entete du formaulaire d\'adhesion en ligne',0);
insert into llx_const(name, value, type, note, visible) values ('ADH_CARD_HEADER_TEXT','%ANNEE%','string','Texte imprime sur le haut de la carte adherent',0);
insert into llx_const(name, value, type, note, visible) values ('ADH_CARD_FOOTER_TEXT','Association FreeLUG http://www.freelug.org/','string','Texte imprime sur le bas de la carte adherent',0);
insert into llx_const(name, value, type, note, visible) values ('ADH_CARD_TEXT','%TYPE% n° %ID%\r\n%PRENOM% %NOM%\r\n<%EMAIL%>\r\n%ADRESSE%\r\n%CP% %VILLE%\r\n%PAYS%','texte','Texte imprime sur la carte adherent',0);
