-- ============================================================================--
-- Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2004 Benoit Mortier <benoit.mortier@opensides.be>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- Valeurs pour les bases de langues francaises
--
-- $Id$
-- $Source$
--
-- ============================================================================--

insert into llx_cond_reglement values (1,1,1, 'A réception','Réception de facture',0,0);
insert into llx_cond_reglement values (2,2,1, '30 jours','Réglement à 30 jours',0,30);
insert into llx_cond_reglement values (3,3,1, '30 jours fin de mois','Réglement à 30 jours fin de mois',1,30);
insert into llx_cond_reglement values (4,4,1, '60 jours','Réglement à 60 jours',0,60);
insert into llx_cond_reglement values (5,5,1, '60 jours fin de mois','Réglement à 60 jours fin de mois',1,60);


insert into llx_sqltables (name, loaded) values ('llx_album',0);

--
-- Définition des action de workflow
--

delete from llx_action_def;
insert into llx_action_def (rowid,titre,description,objet_type) values (1,'Validation fiche intervention','Déclenché lors de la validation d\'une fiche d\'intervention','ficheinter');
insert into llx_action_def (rowid,titre,description,objet_type) values (2,'Validation facture','Déclenché lors de la validation d\'une facture','facture');

--
-- Boites
--

delete from llx_boxes_def;

delete from llx_boxes;

--
-- Constantes de configuration
--

insert into llx_const (name, value, type, note) values ('MAIN_MONNAIE','euros','chaine','Monnaie');
insert into llx_const (name, value, type, note) values ('MAIN_UPLOAD_DOC','1','chaine','Authorise l\'upload de document');
insert into llx_const (name, value, type, note) values ('MAIN_NOT_INSTALLED','1','chaine','Test d\'installation');
insert into llx_const (name, value, type, note) values ('MAIN_MAIL_FROM','dolibarr-robot@domain.com','chaine','EMail emetteur pour les notifications automatiques Dolibarr');

insert into llx_const (name, value, type, note) values ('MAIN_START_YEAR','2004','chaine','Année de départ');

insert into llx_const (name, value, type, note) values ('MAIN_TITLE','Dolibarr','chaine','Titre des pages');
insert into llx_const (name, value, type, note) values ('MAIN_DEBUG','1','yesno','Debug ..');

insert into llx_const (name, value, type, note, visible) values ('COMPTA_BANK_FACTURES','1','yesno','Menu factures dans la partie bank',0);
insert into llx_const (name, value, type, note, visible) values ('COMPTA_ONLINE_PAYMENT_BPLC','1','yesno','Système de gestion de la banque populaire de Lorraine',0);

--
-- IHM
--

insert into llx_const (name, value, type, note, visible) values ('MAIN_THEME','yellow','chaine','Thème par défaut',0);
insert into llx_const (name, value, type, note, visible) values ('SIZE_LISTE_LIMIT','20','chaine','Taille des listes',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_MENU_BARRETOP','default.php','chaine','Module de gestion de la barre de menu du haut',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_LANG_DEFAULT','fr','chaine','Langue par défaut pour les écrans Dolibarr',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_SEARCHFORM_CONTACT','1','yesno','Affichage formulaire de recherche des Contacts dans la barre de gauche',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_SEARCHFORM_SOCIETE','1','yesno','Affichage formulaire de recherche des Sociétés dans la barre de gauche',0);

--
-- Dons
--

insert into llx_const (name, value, type) values ('DONS_FORM','fsfe.fr.php','chaine');

--
-- Mail Adherent
--

insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_FROM','adherents@domain.com','chaine','From des mails adherents',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_RESIL','Votre adhesion sur %SERVEUR% vient d\'etre resilie.\r\nNous esperons vous revoir tres bientot','texte','Mail de Resiliation',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_VALID','MAIN\r\nVotre adhesion vient d\'etre validee. \r\nVoici le rappel de vos coordonnees (toute information erronee entrainera la non validation de votre inscription) :\r\n\r\n%INFO%\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante : \r\n%SERVEUR%public/adherents/','texte','Mail de validation',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_EDIT','Voici le rappel des coordonnees que vous avez modifiees (toute information erronee entrainera la non validation de votre inscription) :\r\n\r\n%INFO%\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante :\r\n%SERVEUR%public/adherents/','texte','Mail d\'edition',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_NEW','Merci de votre inscription. Votre adhesion devrait etre rapidement validee.\r\nVoici le rappel des coordonnees que vous avez rentrees (toute information erronee entrainera la non validation de votre inscription) :\r\n\r\n%INFO%\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante :\r\n%SERVEUR%public/adherents/','texte','Mail de nouvel inscription',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_COTIS','Bonjour %PRENOM%,\r\nMerci de votre inscription.\r\nCet email confirme que votre cotisation a ete recue et enregistree.\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante :\r\n%SERVEUR%public/adherents/','texte','Mail de validation de cotisation',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_VALID_SUBJECT','Votre adhésion a ete validée sur %SERVEUR%','chaine','sujet du mail de validation',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_RESIL_SUBJECT','Resiliation de votre adhesion sur %SERVEUR%','chaine','sujet du mail de resiliation',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_COTIS_SUBJECT','Recu de votre cotisation','chaine','sujet du mail de validation de cotisation',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_NEW_SUBJECT','Bienvenue sur %SERVEUR%','chaine','Sujet du mail de nouvelle adhesion',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_EDIT_SUBJECT','Votre fiche a ete editee sur %SERVEUR%','chaine','Sujet du mail d\'edition',0);

--
-- Mailman
--

insert into llx_const (name, value, type, note, visible) values ('ADHERENT_USE_MAILMAN','0','yesno','Utilisation de Mailman',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAILMAN_UNSUB_URL','http://%SERVER%/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%ADMINPW%&user=%EMAIL%','chaine','Url de desinscription aux listes mailman',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAILMAN_URL','http://%SERVER%/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%ADMINPW%&send_welcome_msg_to_this_batch=1&subscribees=%EMAIL%','chaine','url pour les inscriptions mailman',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAILMAN_LISTS','test-test,test-test2','chaine','Listes auxquelles inscrire les nouveaux adherents',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAILMAN_ADMINPW','','chaine','Mot de passe Admin des liste mailman',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAILMAN_SERVER','lists.domain.com','chaine','Serveur hebergeant les interfaces d\'Admin des listes mailman',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAILMAN_LISTS_COTISANT','','chaine','Liste(s) auxquelles les nouveaux cotisants sont inscris automatiquement',0);

--
-- Glasnost
--

insert into llx_const (name, value, type, note, visible) values ('ADHERENT_USE_GLASNOST','0','yesno','utilisation de glasnost ?',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_GLASNOST_SERVEUR','glasnost.j1b.org','chaine','serveur glasnost',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_GLASNOST_USER','user','chaine','Administrateur glasnost',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_GLASNOST_PASS','password','chaine','password de l\'administrateur',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_USE_GLASNOST_AUTO','0','yesno','inscription automatique a glasnost ?',0);

--
-- SPIP
--

insert into llx_const (name, value, type, note, visible) values ('ADHERENT_USE_SPIP','0','yesno','Utilisation de SPIP ?',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_USE_SPIP_AUTO','0','yesno','Utilisation de SPIP automatiquement',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_SPIP_USER','user','chaine','user spip',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_SPIP_PASS','pass','chaine','Pass de connection',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_SPIP_SERVEUR','localhost','chaine','serveur spip',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_SPIP_DB','spip','chaine','db spip',0);

--
-- cartes adherents
--

insert into llx_const (name, value, type, note, visible) values ('ADHERENT_TEXT_NEW_ADH','','texte','Texte d\'entete du formaulaire d\'adhesion en ligne',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_CARD_HEADER_TEXT','%ANNEE%','chaine','Texte imprime sur le haut de la carte adherent',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_CARD_FOOTER_TEXT','Association FreeLUG http://www.freelug.org/','chaine','Texte imprime sur le bas de la carte adherent',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_CARD_TEXT','%TYPE% n° %ID%\r\n%PRENOM% %NOM%\r\n<%EMAIL%>\r\n%ADRESSE%\r\n%CP% %VILLE%\r\n%PAYS%','texte','Texte imprime sur la carte adherent',0);

--
-- OsCommerce
--

insert into llx_const (name, value, type) values ('DB_NAME_OSC','catalog','chaine');
insert into llx_const (name, value, type) values ('OSC_LANGUAGE_ID','1','chaine');
insert into llx_const (name, value, type) values ('OSC_CATALOG_URL','http://osc.lafrere.lan/','chaine');

--
-- Factures
--

insert into llx_const (name, value, type) values ('FAC_PDF_INTITULE','Facture','chaine');
insert into llx_const (name, value, type) values ('FAC_PDF_MEL','facture@societe.com','chaine');
insert into llx_const (name, value, type) values ('FAC_PDF_WWW','http://www.societe.com','chaine');
insert into llx_const (name, value, type) values ('FAC_PDF_LOGO','/htdocs/documents/logo','chaine');
insert into llx_const (name, value, type) values ('FAC_CAPITAL_EURO','18600','chaine');
insert into llx_const (name, value, type) values ('FAC_PDF_TVA_INTRA','BE 443 698 678','chaine');
insert into llx_const (name, value, type) values ('FAC_PDF_RCS','634 674','chaine');

--
-- Types de charges
--

delete from llx_c_chargesociales;
insert into llx_c_chargesociales (id,libelle,deductible) values ( 1, 'Allocations familiales',1);
insert into llx_c_chargesociales (id,libelle,deductible) values ( 2, 'GSG Deductible',1);
insert into llx_c_chargesociales (id,libelle,deductible) values ( 3, 'GSG/CRDS NON Deductible',0);

--
-- Types action
--

delete from llx_c_actioncomm;
insert into llx_c_actioncomm (id,libelle) values ( 0, '-');
insert into llx_c_actioncomm (id,libelle) values ( 1, 'Appel Téléphonique');
insert into llx_c_actioncomm (id,libelle) values ( 2, 'Envoi Fax');
insert into llx_c_actioncomm (id,libelle) values ( 3, 'Envoi Proposition');
insert into llx_c_actioncomm (id,libelle) values ( 4, 'Envoi Email');
insert into llx_c_actioncomm (id,libelle) values ( 5, 'Prendre Rendez-vous');
insert into llx_c_actioncomm (id,libelle) values ( 9, 'Envoi Facture');
insert into llx_c_actioncomm (id,libelle) values (10, 'Relance effectuée');
insert into llx_c_actioncomm (id,libelle) values (11, 'Clôture');

--
--
--

delete from llx_c_stcomm;
insert into llx_c_stcomm (id,libelle) values (-1, 'NE PAS CONTACTER');
insert into llx_c_stcomm (id,libelle) values ( 0, 'Jamais contacté');
insert into llx_c_stcomm (id,libelle) values ( 1, 'A contacter');
insert into llx_c_stcomm (id,libelle) values ( 2, 'Contact en cours');
insert into llx_c_stcomm (id,libelle) values ( 3, 'Contactée');


--
-- Types d'entreprise
--

delete from llx_c_typent;
insert into llx_c_typent (id,libelle) values (  0, 'Indifférent');
insert into llx_c_typent (id,libelle) values (  1, 'Start-up');
insert into llx_c_typent (id,libelle) values (  2, 'Grand groupe');
insert into llx_c_typent (id,libelle) values (  3, 'PME/PMI');
insert into llx_c_typent (id,libelle) values (  4, 'Administration');
insert into llx_c_typent (id,libelle) values (100, 'Autres');

--
-- Pays
--

delete from llx_c_pays;
insert into llx_c_pays (rowid,libelle,code) values (1, 'France',          'FR');
insert into llx_c_pays (rowid,libelle,code) values (2, 'Belgique',        'BE');
insert into llx_c_pays (rowid,libelle,code) values (3, 'Italie',          'IT');
insert into llx_c_pays (rowid,libelle,code) values (4, 'Espagne',         'ES');
insert into llx_c_pays (rowid,libelle,code) values (5, 'Allemagne',       'DE');
insert into llx_c_pays (rowid,libelle,code) values (6, 'Suisse',          'CH');
insert into llx_c_pays (rowid,libelle,code) values (7, 'Royaume uni',     'GB');
insert into llx_c_pays (rowid,libelle,code) values (8, 'Irlande',         'IE');
insert into llx_c_pays (rowid,libelle,code) values (9, 'Chine',           'CN');
insert into llx_c_pays (rowid,libelle,code) values (10, 'Tunisie',        'TN');
insert into llx_c_pays (rowid,libelle,code) values (11, 'Etats Unis',     'US');
insert into llx_c_pays (rowid,libelle,code) values (12, 'Maroc',          'MA');
insert into llx_c_pays (rowid,libelle,code) values (13, 'Algérie',        'DZ');
insert into llx_c_pays (rowid,libelle,code) values (14, 'Canada',         'CA');
insert into llx_c_pays (rowid,libelle,code) values (15, 'Togo',           'TG');
insert into llx_c_pays (rowid,libelle,code) values (16, 'Gabon',          'GA');
insert into llx_c_pays (rowid,libelle,code) values (17, 'Pays Bas',       'NL');
insert into llx_c_pays (rowid,libelle,code) values (18, 'Hongrie',        'HU');
insert into llx_c_pays (rowid,libelle,code) values (19, 'Russie',         'RU');
insert into llx_c_pays (rowid,libelle,code) values (20, 'Suède',          'SE');
insert into llx_c_pays (rowid,libelle,code) values (21, 'Côte d\'Ivoire', 'CI');
insert into llx_c_pays (rowid,libelle,code) values (23, 'Sénégal',        'SN');
insert into llx_c_pays (rowid,libelle,code) values (24, 'Argentine',      'AR');
insert into llx_c_pays (rowid,libelle,code) values (25, 'Cameroun',       'CM');

--
-- Regions
--

insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (0,0,0,'0',0,'-');
-- Regions de France
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (101,1,  1,'97105',3,'Guadeloupe');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (102,1,  2,'97209',3,'Martinique');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (103,1,  3,'97302',3,'Guyane');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (104,1,  4,'97411',3,'Réunion');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (105,1, 11,'75056',1,'Île-de-France');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (106,1, 21,'51108',0,'Champagne-Ardenne');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (107,1, 22,'80021',0,'Picardie');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (108,1, 23,'76540',0,'Haute-Normandie');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (109,1, 24,'45234',2,'Centre');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (110,1, 25,'14118',0,'Basse-Normandie');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (111,1, 26,'21231',0,'Bourgogne');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (112,1, 31,'59350',2,'Nord-Pas-de-Calais');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (113,1, 41,'57463',0,'Lorraine');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (114,1, 42,'67482',1,'Alsace');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (115,1, 43,'25056',0,'Franche-Comté');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (116,1, 52,'44109',4,'Pays de la Loire');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (117,1, 53,'35238',0,'Bretagne');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (118,1, 54,'86194',2,'Poitou-Charentes');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (119,1, 72,'33063',1,'Aquitaine');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (120,1, 73,'31555',0,'Midi-Pyrénées');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (121,1, 74,'87085',2,'Limousin');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (122,1, 82,'69123',2,'Rhône-Alpes');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (123,1, 83,'63113',1,'Auvergne');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (124,1, 91,'34172',2,'Languedoc-Roussillon');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (125,1, 93,'13055',0,'Provence-Alpes-Côte d\'Azur');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (126,1, 94,'2A004',0,'Corse');

--
-- Regions de Belgique
--

insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (201,2,201,'',1,'Flandre');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (202,2,202,'',2,'Wallonie');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (203,2,203,'',3,'Bruxelles-Capitale');


--
-- Departements/Cantons/Provinces
--

insert into llx_c_departements (rowid, fk_region, code_departement,cheflieu,tncc,ncc,nom) values (0,0,0,'0',0,'-','-');
-- Departements de France
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'01','01053',5,'AIN','Ain');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (22,'02','02408',5,'AISNE','Aisne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (83,'03','03190',5,'ALLIER','Allier');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'04','04070',4,'ALPES-DE-HAUTE-PROVENCE','Alpes-de-Haute-Provence');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'05','05061',4,'HAUTES-ALPES','Hautes-Alpes');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'06','06088',4,'ALPES-MARITIMES','Alpes-Maritimes');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'07','07186',5,'ARDECHE','Ardèche');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (21,'08','08105',4,'ARDENNES','Ardennes');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (73,'09','09122',5,'ARIEGE','Ariège');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (21,'10','10387',5,'AUBE','Aube');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (91,'11','11069',5,'AUDE','Aude');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (73,'12','12202',5,'AVEYRON','Aveyron');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'13','13055',4,'BOUCHES-DU-RHONE','Bouches-du-Rhône');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (25,'14','14118',2,'CALVADOS','Calvados');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (83,'15','15014',2,'CANTAL','Cantal');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (54,'16','16015',3,'CHARENTE','Charente');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (54,'17','17300',3,'CHARENTE-MARITIME','Charente-Maritime');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (24,'18','18033',2,'CHER','Cher');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (74,'19','19272',3,'CORREZE','Corrèze');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (94,'2A','2A004',3,'CORSE-DU-SUD','Corse-du-Sud');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (94,'2B','2B033',3,'HAUTE-CORSE','Haute-Corse');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (26,'21','21231',3,'COTE-D\'OR','Côte-d\'Or');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (53,'22','22278',4,'COTES-D\'ARMOR','Côtes-d\'Armor');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (74,'23','23096',3,'CREUSE','Creuse');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (72,'24','24322',3,'DORDOGNE','Dordogne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (43,'25','25056',2,'DOUBS','Doubs');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'26','26362',3,'DROME','Drôme');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (23,'27','27229',5,'EURE','Eure');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (24,'28','28085',1,'EURE-ET-LOIR','Eure-et-Loir');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (53,'29','29232',2,'FINISTERE','Finistère');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (91,'30','30189',2,'GARD','Gard');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (73,'31','31555',3,'HAUTE-GARONNE','Haute-Garonne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (73,'32','32013',2,'GERS','Gers');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (72,'33','33063',3,'GIRONDE','Gironde');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (91,'34','34172',5,'HERAULT','Hérault');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (53,'35','35238',1,'ILLE-ET-VILAINE','Ille-et-Vilaine');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (24,'36','36044',5,'INDRE','Indre');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (24,'37','37261',1,'INDRE-ET-LOIRE','Indre-et-Loire');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'38','38185',5,'ISERE','Isère');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (43,'39','39300',2,'JURA','Jura');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (72,'40','40192',4,'LANDES','Landes');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (24,'41','41018',0,'LOIR-ET-CHER','Loir-et-Cher');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'42','42218',3,'LOIRE','Loire');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (83,'43','43157',3,'HAUTE-LOIRE','Haute-Loire');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (52,'44','44109',3,'LOIRE-ATLANTIQUE','Loire-Atlantique');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (24,'45','45234',2,'LOIRET','Loiret');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (73,'46','46042',2,'LOT','Lot');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (72,'47','47001',0,'LOT-ET-GARONNE','Lot-et-Garonne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (91,'48','48095',3,'LOZERE','Lozère');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (52,'49','49007',0,'MAINE-ET-LOIRE','Maine-et-Loire');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (25,'50','50502',3,'MANCHE','Manche');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (21,'51','51108',3,'MARNE','Marne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (21,'52','52121',3,'HAUTE-MARNE','Haute-Marne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (52,'53','53130',3,'MAYENNE','Mayenne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (41,'54','54395',0,'MEURTHE-ET-MOSELLE','Meurthe-et-Moselle');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (41,'55','55029',3,'MEUSE','Meuse');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (53,'56','56260',2,'MORBIHAN','Morbihan');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (41,'57','57463',3,'MOSELLE','Moselle');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (26,'58','58194',3,'NIEVRE','Nièvre');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (31,'59','59350',2,'NORD','Nord');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (22,'60','60057',5,'OISE','Oise');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (25,'61','61001',5,'ORNE','Orne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (31,'62','62041',2,'PAS-DE-CALAIS','Pas-de-Calais');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (83,'63','63113',2,'PUY-DE-DOME','Puy-de-Dôme');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (72,'64','64445',4,'PYRENEES-ATLANTIQUES','Pyrénées-Atlantiques');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (73,'65','65440',4,'HAUTES-PYRENEES','Hautes-Pyrénées');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (91,'66','66136',4,'PYRENEES-ORIENTALES','Pyrénées-Orientales');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (42,'67','67482',2,'BAS-RHIN','Bas-Rhin');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (42,'68','68066',2,'HAUT-RHIN','Haut-Rhin');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'69','69123',2,'RHONE','Rhône');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (43,'70','70550',3,'HAUTE-SAONE','Haute-Saône');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (26,'71','71270',0,'SAONE-ET-LOIRE','Saône-et-Loire');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (52,'72','72181',3,'SARTHE','Sarthe');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'73','73065',3,'SAVOIE','Savoie');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'74','74010',3,'HAUTE-SAVOIE','Haute-Savoie');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'75','75056',0,'PARIS','Paris');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (23,'76','76540',3,'SEINE-MARITIME','Seine-Maritime');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'77','77288',0,'SEINE-ET-MARNE','Seine-et-Marne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'78','78646',4,'YVELINES','Yvelines');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (54,'79','79191',4,'DEUX-SEVRES','Deux-Sèvres');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (22,'80','80021',3,'SOMME','Somme');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (73,'81','81004',2,'TARN','Tarn');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (73,'82','82121',0,'TARN-ET-GARONNE','Tarn-et-Garonne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'83','83137',2,'VAR','Var');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'84','84007',0,'VAUCLUSE','Vaucluse');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (52,'85','85191',3,'VENDEE','Vendée');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (54,'86','86194',3,'VIENNE','Vienne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (74,'87','87085',3,'HAUTE-VIENNE','Haute-Vienne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (41,'88','88160',4,'VOSGES','Vosges');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (26,'89','89024',5,'YONNE','Yonne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (43,'90','90010',0,'TERRITOIRE DE BELFORT','Territoire de Belfort');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'91','91228',5,'ESSONNE','Essonne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'92','92050',4,'HAUTS-DE-SEINE','Hauts-de-Seine');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'93','93008',3,'SEINE-SAINT-DENIS','Seine-Saint-Denis');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'94','94028',2,'VAL-DE-MARNE','Val-de-Marne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'95','95500',2,'VAL-D\'OISE','Val-d\'Oise');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values ( 1,'971','97105',3,'GUADELOUPE','Guadeloupe');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values ( 2,'972','97209',3,'MARTINIQUE','Martinique');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values ( 3,'973','97302',3,'GUYANE','Guyane');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values ( 4,'974','97411',3,'REUNION','Réunion');

--
-- Provinces de Belgique - en Francais
--

insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (201,'01','',1,'ANVERS','Anvers');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (203,'02','',3,'BRUXELLES-CAPITALE','Bruxelles-Capitale');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (202,'03','',2,'BRABANT-WALLON','Brabant-Wallon');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (201,'04','',1,'BRABANT-FLAMAND','Brabant-Flamand');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (201,'05','',1,'FLANDRE-OCCIDENTALE','Flandre-Occidentale');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (201,'06','',1,'FLANDRE-ORIENTALE','Flandre-Orientale');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (202,'07','',2,'HAINAUT','Hainaut');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (201,'08','',2,'LIEGE','Liège');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (202,'09','',1,'LIMBOURG','Limbourg');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (202,'10','',2,'LUXEMBOURG','Luxembourg');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (201,'11','',2,'NAMUR','Namur');

--
-- Provinces de Belgique - en Néerlandais
--

insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (201,'01','',1,'ANTWERP','Antwerp');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (203,'02','',3,'BRUXELLES-CAPITALE','Bruxelles-Capitale');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (201,'04','',1,'VLAMS-BRABANT','Vlams-Brabant');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (201,'05','',1,'WEST-VLANDEREN','West-Vlanderen');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (201,'06','',1,'OOST-VLANDEREN','Oost-Vlanderen');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (202,'09','',1,'LIMBURG','Limburg');

--
-- Effectif des sociétés
--

delete from llx_c_effectif;
insert into llx_c_effectif (id,libelle) values (0,  'Non spécifié');
insert into llx_c_effectif (id,libelle) values (1,  '1 - 5');
insert into llx_c_effectif (id,libelle) values (2,  '6 - 10');
insert into llx_c_effectif (id,libelle) values (3,  '11 - 50');
insert into llx_c_effectif (id,libelle) values (4,  '51 - 100');
insert into llx_c_effectif (id,libelle) values (5,  '100 - 500');
insert into llx_c_effectif (id,libelle) values (6,  '> 500');

delete from llx_c_paiement;
insert into llx_c_paiement (id,code,libelle,type) values (0, '-', 3);
insert into llx_c_paiement (id,code,libelle,type) values (1, 'TIP', 'TIP', 1);
insert into llx_c_paiement (id,code,libelle,type) values (2, 'VIR', 'Virement', 2);
insert into llx_c_paiement (id,code,libelle,type) values (3, 'PRE', 'Prélèvement', 1);
insert into llx_c_paiement (id,code,libelle,type) values (4, 'LIQ', 'Liquide', 0);
insert into llx_c_paiement (id,code,libelle,type) values (5, 'VAD', 'Paiement en ligne', 0);
insert into llx_c_paiement (id,code,libelle,type) values (6, 'CB',  'Carte Bancaire', 1);
insert into llx_c_paiement (id,code,libelle,type) values (7, 'CHQ', 'Chèque', 2);

delete from llx_c_propalst;
insert into llx_c_propalst (id,label) values (0, 'Brouillon');
insert into llx_c_propalst (id,label) values (1, 'Ouverte');
insert into llx_c_propalst (id,label) values (2, 'Signée');
insert into llx_c_propalst (id,label) values (3, 'Non Signée');
insert into llx_c_propalst (id,label) values (4, 'Facturée');

--
-- Formes juridiques
--

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (0, 0,'Non renseignée');

-- Pour la France: Extrait de http://www.insee.fr/fr/nom_def_met/nomenclatures/cj/cjniveau2.htm
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,11,'Artisan Commerçant');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,12,'Commerçant');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,13,'Artisan');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,14,'Officier public ou ministériel');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,15,'Profession libérale');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,16,'Exploitant agricole');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,17,'Agent commercial');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,18,'Associé Gérant de société');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,19,'(Autre) personne physique');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,21,'Indivision');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,22,'Société créée de fait');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,23,'Société en participation');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,27,'Paroisse hors zone concordataire');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,29,'Autre groupement de droit privé non doté de la personnalité morale');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,31,'Personne morale de droit étranger, immatriculée au RCS (registre du commerce et des sociétés)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,32,'Personne morale de droit étranger, non immatriculée au RCS');

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,41,'Établissement public ou régie à caractère industriel ou commercial');

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,51,'Société coopérative commerciale particulière');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,52,'Société en nom collectif');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,53,'Société en commandite');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,54,'Société à responsabilité limité (SARL)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,55,'Société anonyme à conseil d\'administration');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,56,'Société anonyme à directoire');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,57,'Société par actions simplifiée');

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,61,'Caisse d\'épargne et de prévoyance');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,62,'Groupement d\'intérêt économique');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,63,'Société coopérative agricole');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,64,'Société non commerciale d\'assurances');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,65,'Société civile');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,69,'Autres personnes de droit privé inscrites au registre du commerce et des sociétés');

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,71,'Administration de l\'état');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,72,'Collectivité territoriale');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,73,'Établissement public administratif');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,74,'Autre personne morale de droit public administratif');

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,81,'Organisme gérant un régime de protection social à adhésion obligatoire');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,82,'Organisme mutualiste');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,83,'Comité d\'entreprise');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,84,'Organisme professionnel');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,85,'Organisme de retraite à adhésion non obligatoire');

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,91,'Syndicat de propriétaires');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,92,'Association loi 1901 ou assimilé');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,93,'Fondation');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,99,'Autre personne morale de droit privé');

--
-- Pour la Belgique
--

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,100,'Indépendant');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,101,'SPRL - Société à responsabilité limitée');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,102,'SA   - Société Anonyme');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,103,'SCRL - Société coopérative à responsabilité limitée');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,104,'ASBL - Association sans but Lucratif');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,105,'SCRI - Société coopérative à responsabilité illimitée');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,106,'SCS  - Société en comanndite simple');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,107,'SCA  - Société en commandite par action');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,109,'SNC  - Société en nom collectif');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,107,'GIE  - Groupement d\'intérêt économique');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2,109,'GEIE - Groupement européen d\'intérêt économique');

--
-- Formules de politesses
--

insert into llx_c_civilite (rowid, fk_pays, civilite, active) values (1, 2, 'Madame', 1);
insert into llx_c_civilite (rowid, fk_pays, civilite, active) values (2, 2, 'Mevrouw', 1);
insert into llx_c_civilite (rowid, fk_pays, civilite, active) values (3, 2, 'Monsieur', 1);
insert into llx_c_civilite (rowid, fk_pays, civilite, active) values (4, 2, 'Meneer', 1);
insert into llx_c_civilite (rowid, fk_pays, civilite, active) values (5, 2, 'Mademoiselle', 1);
insert into llx_c_civilite (rowid, fk_pays, civilite, active) values (6, 2, 'Juffrouw', 1);
insert into llx_c_civilite (rowid, fk_pays, civilite, active) values (7, 2, 'Maître', 1);

--
-- Descriptif du plan comptable FR PCG99-ABREGE
--

insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (1,1,'PCG99-ABREGE','XXXXXX','Capital','101');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (2,1,'PCG99-ABREGE','XXXXXX','Ecarts de réévaluation ','105');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (3,1,'PCG99-ABREGE','XXXXXX','Réserve légale','1061');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (4,1,'PCG99-ABREGE','XXXXXX','Réserves statutaires ou contractuelles ','1063');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (5,1,'PCG99-ABREGE','XXXXXX','Réserves réglementées ','1064');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (6,1,'PCG99-ABREGE','XXXXXX','Autres réserves','1068');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (7,1,'PCG99-ABREGE','XXXXXX','Compte de l''exploitant','108');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (8,1,'PCG99-ABREGE','XXXXXX','résultat de l''exercice  ','12');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (9,1,'PCG99-ABREGE','XXXXXX','Amortissements dérogatoires ','145');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (10,1,'PCG99-ABREGE','XXXXXX','Provision spéciale de réévaluation ','146');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (11,1,'PCG99-ABREGE','XXXXXX','Plus-values réinvesties ','147');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (12,1,'PCG99-ABREGE','XXXXXX','Autres provisions réglementées ','148');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (13,1,'PCG99-ABREGE','XXXXXX','Provisions pour risques et charges ','15');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (14,1,'PCG99-ABREGE','XXXXXX','emprunts et dettes assimilees','16');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (15,1,'PCG99-ABREGE','XXXXXX','immobilisations incorporelles','20');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (16,1,'PCG99-ABREGE','XXXXXX','Frais d''établissement','201');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (17,1,'PCG99-ABREGE','XXXXXX','Droit au bail','206');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (18,1,'PCG99-ABREGE','XXXXXX','Fonds commercial','207');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (19,1,'PCG99-ABREGE','XXXXXX','Autres immobilisations incorporelles ','208');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (20,1,'PCG99-ABREGE','XXXXXX','immobilisations corporelles','21');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (21,1,'PCG99-ABREGE','XXXXXX','immobilisations en cours','23');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (22,1,'PCG99-ABREGE','XXXXXX','autres immobilisations financieres ','27');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (23,1,'PCG99-ABREGE','XXXXXX','Amortissements des immobilisations incorporelles ','280');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (24,1,'PCG99-ABREGE','XXXXXX','Amortissements des immobilisations corporelles ','281');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (25,1,'PCG99-ABREGE','XXXXXX','Provisions pour dépréciation des immobilisations incorporelles ','290');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (26,1,'PCG99-ABREGE','XXXXXX','Provisions pour dépréciation des immobilisations corporelles  ','291');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (27,1,'PCG99-ABREGE','XXXXXX','Provisions pour dépréciation des autres immobilisations financières ','297');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (28,1,'PCG99-ABREGE','XXXXXX','matieres premières  ','31');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (29,1,'PCG99-ABREGE','XXXXXX','autres approvisionnements','32');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (30,1,'PCG99-ABREGE','XXXXXX','en-cours de production de biens ','33');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (31,1,'PCG99-ABREGE','XXXXXX','en-cours de production de services ','34');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (32,1,'PCG99-ABREGE','XXXXXX','stocks de produits','35');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (33,1,'PCG99-ABREGE','XXXXXX','stocks de marchandises','37');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (34,1,'PCG99-ABREGE','XXXXXX','Provisions pour dépréciation des matières premières  ','391');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (35,1,'PCG99-ABREGE','XXXXXX','Provisions pour dépréciation des autres approvisionnements ','392');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (36,1,'PCG99-ABREGE','XXXXXX','Provisions pour dépréciation des en-cours de production de biens ','393');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (37,1,'PCG99-ABREGE','XXXXXX','Provisions pour dépréciation des en-cours de production de services ','394');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (38,1,'PCG99-ABREGE','XXXXXX','Provisions pour dépréciation des stocks de produits ','395');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (39,1,'PCG99-ABREGE','XXXXXX','Provisions pour dépréciation des stocks de marchandises ','397');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (40,1,'PCG99-ABREGE','XXXXXX','Fournisseurs et Comptes rattachés ','400');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (41,1,'PCG99-ABREGE','XXXXXX','Fournisseurs débiteurs','409');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (42,1,'PCG99-ABREGE','XXXXXX','Clients et Comptes rattachés ','410');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (43,1,'PCG99-ABREGE','XXXXXX','Clients créditeurs','419');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (44,1,'PCG99-ABREGE','XXXXXX','Personnel','421');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (45,1,'PCG99-ABREGE','XXXXXX','Personnel','428');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (46,1,'PCG99-ABREGE','XXXXXX','Sécurité sociale et autres organismes sociaux ','43');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (47,1,'PCG99-ABREGE','XXXXXX','Etat','444');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (48,1,'PCG99-ABREGE','XXXXXX','Etat','445');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (49,1,'PCG99-ABREGE','XXXXXX','Autres impôts, taxes et versements assimilés ','447');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (50,1,'PCG99-ABREGE','XXXXXX','Groupe et associes','45');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (51,1,'PCG99-ABREGE','XXXXXX','Associés','455');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (52,1,'PCG99-ABREGE','XXXXXX','Débiteurs divers et créditeurs divers ','46');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (53,1,'PCG99-ABREGE','XXXXXX','comptes transitoires ou d''attente ','47');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (54,1,'PCG99-ABREGE','XXXXXX','Charges à répartir sur plusieurs exercices ','481');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (55,1,'PCG99-ABREGE','XXXXXX','Charges constatées d''avance ','486');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (56,1,'PCG99-ABREGE','XXXXXX','Produits constatés d''avance ','487');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (57,1,'PCG99-ABREGE','XXXXXX','Provisions pour dépréciation des comptes de clients ','491');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (58,1,'PCG99-ABREGE','XXXXXX','Provisions pour dépréciation des comptes de débiteurs divers ','496');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (59,1,'PCG99-ABREGE','XXXXXX','valeurs mobilières de placement ','50');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (60,1,'PCG99-ABREGE','BANK','banques, établissements financiers et assimilés ','51');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (61,1,'PCG99-ABREGE','CASH','Caisse','53');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (62,1,'PCG99-ABREGE','XXXXXX','régies d''avance et accréditifs','54');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (63,1,'PCG99-ABREGE','XXXXXX','virements internes','58');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (64,1,'PCG99-ABREGE','XXXXXX','Provisions pour dépréciation des valeurs mobilières de placement ','590');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (65,1,'PCG99-ABREGE','XXXXXX','Achats ','60');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (66,1,'PCG99-ABREGE','XXXXXX','variations des stocks  ','603');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (67,1,'PCG99-ABREGE','XXXXXX','Services extérieurs','61');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (68,1,'PCG99-ABREGE','XXXXXX','Autres services extérieurs ','62');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (69,1,'PCG99-ABREGE','XXXXXX','Impôts, taxes et versements assimiles ','63');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (70,1,'PCG99-ABREGE','XXXXXX','Rémunérations du personnel ','641');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (71,1,'PCG99-ABREGE','XXXXXX','Rémunération du travail de l''exploitant ','644');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (72,1,'PCG99-ABREGE','XXXXXX','Charges de sécurité sociale et de prévoyance ','645');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (73,1,'PCG99-ABREGE','XXXXXX','Cotisations sociales personnelles de l''exploitant ','646');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (74,1,'PCG99-ABREGE','XXXXXX','Autres charges de gestion courante ','65');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (75,1,'PCG99-ABREGE','XXXXXX','Charges financières','66');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (76,1,'PCG99-ABREGE','XXXXXX','Charges exceptionnelles','67');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (77,1,'PCG99-ABREGE','XXXXXX','Dotations aux amortissements et aux provisions','681');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (78,1,'PCG99-ABREGE','XXXXXX','Dotations aux amortissements et aux provisions','686');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (79,1,'PCG99-ABREGE','XXXXXX','Dotations aux amortissements et aux provisions','687');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (80,1,'PCG99-ABREGE','XXXXXX','Participation des salariés aux résultats ','691');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (81,1,'PCG99-ABREGE','XXXXXX','Impôts sur les bénéfices ','695');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (82,1,'PCG99-ABREGE','XXXXXX','Imposition forfaitaire annuelle des sociétés ','697');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (83,1,'PCG99-ABREGE','XXXXXX','Produits','699');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (84,1,'PCG99-ABREGE','XXXXXX','Ventes de produits finis','701');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (85,1,'PCG99-ABREGE','XXXXXX','Prestations de services','706');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (86,1,'PCG99-ABREGE','XXXXXX','Ventes de marchandises','707');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (87,1,'PCG99-ABREGE','XXXXXX','Produits des activités annexes ','708');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (88,1,'PCG99-ABREGE','XXXXXX','Rabais, remises et ristournes accordés par l''entreprise ','709');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (89,1,'PCG99-ABREGE','XXXXXX','Variation des stocks  ','713');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (90,1,'PCG99-ABREGE','XXXXXX','Production immobilisée','72');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (91,1,'PCG99-ABREGE','XXXXXX','Produits nets partiels sur opérations à long terme ','73');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (92,1,'PCG99-ABREGE','XXXXXX','Subventions d''exploitation','74');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (93,1,'PCG99-ABREGE','XXXXXX','Autres produits de gestion courante ','75');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (94,1,'PCG99-ABREGE','XXXXXX','Jetons de présence et rémunérations d''administrateurs, gérants,... ','753');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (95,1,'PCG99-ABREGE','XXXXXX','Ristournes perçues des coopératives  ','754');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (96,1,'PCG99-ABREGE','XXXXXX','Quotes-parts de résultat sur opérations faites en commun ','755');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (97,1,'PCG99-ABREGE','XXXXXX','Produits financiers','76');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (98,1,'PCG99-ABREGE','XXXXXX','Produits exceptionnels','77');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (99,1,'PCG99-ABREGE','XXXXXX','Reprises sur amortissements et provisions  ','781');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (100,1,'PCG99-ABREGE','XXXXXX','Reprises sur provisions pour risques  ','786');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (101,1,'PCG99-ABREGE','XXXXXX','Reprises sur provisions  ','787');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, label, account_number) VALUES (102,1,'PCG99-ABREGE','XXXXXX','Transferts de charges','79');

