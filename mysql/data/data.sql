-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003 Jean-Louis Bergamo <jlb@j1b.org>
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
-- $Id$
-- $Source$
--
-- Valeurs pour les bases de langues francaises
--

--
-- Ne pas place de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

insert into llx_cond_reglement values (1,1,1, "A réception","Réception de facture",0,0);
insert into llx_cond_reglement values (2,2,1, "30 jours","Réglement à 30 jours",0,30);
insert into llx_cond_reglement values (3,3,1, "30 jours fin de mois","Réglement à 30 jours fin de mois",1,30);
insert into llx_cond_reglement values (4,4,1, "60 jours","Réglement à 60 jours",0,60);
insert into llx_cond_reglement values (5,5,1, "60 jours fin de mois","Réglement à 60 jours fin de mois",1,60);


insert into llx_sqltables (name, loaded) values ('llx_album',0);

--
-- Définition des action de workflow
--
delete from llx_action_def;
insert into llx_action_def (rowid,titre,description,objet_type) VALUES (1,'Validation fiche intervention','Déclenché lors de la validation d\'une fiche d\'intervention','ficheinter');
insert into llx_action_def (rowid,titre,description,objet_type) VALUES (2,'Validation facture','Déclenché lors de la validation d\'une facture','facture');

--
-- Boites
--
delete from llx_boxes_def;

delete from llx_boxes;
--
-- Constantes de configuration
--
insert into llx_const(name, value, type, note) values ('MAIN_MONNAIE','euros','chaine','Monnaie');
insert into llx_const(name, value, type, note) values ('MAIN_UPLOAD_DOC','1','chaine','Authorise l\'upload de document');
insert into llx_const(name, value, type, note) values ('MAIN_NOT_INSTALLED','1','chaine','Test d\'installation');

insert into llx_const(name, value, type, note) values ('MAIN_START_YEAR','2003','chaine','Année de départ');

insert into llx_const (name, value, type, note) VALUES ('MAIN_THEME','yellow','chaine','Thème par défaut');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_TITLE','Dolibarr','chaine','Titre des pages');
INSERT INTO llx_const(name, value, type) values ('DONS_FORM','fsfe.fr.php','chaine');


INSERT INTO llx_const(name, value, type, note) values ('MAIN_SEARCHFORM_SOCIETE','1','yesno','Affichage du formulaire de recherche des sociétés dans la barre de gauche');
insert into llx_const(name, value, type, note) values ('MAIN_SEARCHFORM_CONTACT','1','yesno','Affichage du formulaire de recherche des contacts dans la barre de gauche');

insert into llx_const(name, value, type, note) values ('COMPTA_ONLINE_PAYMENT_BPLC','1','yesno','Système de gestion de la banque populaire de Lorraine');

insert into llx_const(name, value, type, note) values ('COMPTA_BANK_FACTURES','1','yesno','Menu factures dans la partie bank');


--
-- Mail Adherent
--
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_RESIL','Votre adhesion sur %SERVEUR% vient d\'etre resilie.\r\nNous esperons vous revoir tres bientot','texte','Mail de Resiliation');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_VALID','MAIN\r\nVotre adhesion vient d\'etre validee. \r\nVoici le rappel de vos coordonnees (toute information erronee entrainera la non validation de votre inscription) :\r\n\r\n%INFO%\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante : \r\n%SERVEUR%public/adherents/','texte','Mail de validation');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_EDIT','Voici le rappel des coordonnees que vous avez modifiees (toute information erronee entrainera la non validation de votre inscription) :\r\n\r\n%INFO%\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante :\r\n%SERVEUR%public/adherents/','texte','Mail d\'edition');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_NEW','Merci de votre inscription. Votre adhesion devrait etre rapidement validee.\r\nVoici le rappel des coordonnees que vous avez rentrees (toute information erronee entrainera la non validation de votre inscription) :\r\n\r\n%INFO%\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante :\r\n%SERVEUR%public/adherents/','texte','Mail de nouvel inscription');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_COTIS','Bonjour %PRENOM%,\r\nMerci de votre inscription.\r\nCet email confirme que votre cotisation a ete recue et enregistree.\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante :\r\n%SERVEUR%public/adherents/','texte','Mail de validation de cotisation');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_VALID_SUBJECT','Votre adhésion a ete validée sur %SERVEUR%','chaine','sujet du mail de validation');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_RESIL_SUBJECT','Resiliation de votre adhesion sur %SERVEUR%','chaine','sujet du mail de resiliation');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_COTIS_SUBJECT','Recu de votre cotisation','chaine','sujet du mail de validation de cotisation');
INSERT INTO llx_const (name, value, type, note) VALUES ('SIZE_LISTE_LIMIT','20','chaine','Taille des listes');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_NEW_SUBJECT','Bienvenue sur %SERVEUR%','chaine','Sujet du mail de nouvelle adhesion');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_EDIT_SUBJECT','Votre fiche a ete editee sur %SERVEUR%','chaine','Sujet du mail d\'edition');
--
-- Mailman
--
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_USE_MAILMAN','0','yesno','Utilisation de Mailman');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_MAILMAN_UNSUB_URL','http://%SERVER%/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%ADMINPW%&user=%EMAIL%','chaine','Url de desinscription aux listes mailman');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_MAILMAN_URL','http://%SERVER%/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%ADMINPW%&send_welcome_msg_to_this_batch=1&subscribees=%EMAIL%','chaine','url pour les inscriptions mailman');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_MAILMAN_LISTS','test-test,test-test2','chaine','Listes auxquelles inscrire les nouveaux adherents');
insert into llx_const(name, value, type, note, visible) values ('ADHERENT_MAILMAN_ADMINPW','','string','Mot de passe Admin des liste mailman',0);
insert into llx_const(name, value, type, note, visible) values ('ADHERENT_MAILMAN_SERVER','lists.domain.com','string','Serveur hebergeant les interfaces d\'Admin des listes mailman',0);
insert into llx_const(name, value, type, note, visible) values ('ADHERENT_MAILMAN_LISTS_COTISANT','','string','Liste(s) auxquelles les nouveaux cotisants sont inscris automatiquement',0);

INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_DEBUG','1','yesno','Debug ..');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_USE_GLASNOST','0','yesno','utilisation de glasnost ?');
--
-- Glasnost
--
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_GLASNOST_SERVEUR','glasnost.j1b.org','chaine','serveur glasnost');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_GLASNOST_USER','user','chaine','Administrateur glasnost');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_GLASNOST_PASS','password','chaine','password de l\'administrateur');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_USE_GLASNOST_AUTO','0','yesno','inscription automatique a glasnost ?');
--
-- SPIP
--
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_USE_SPIP','0','yesno','Utilisation de SPIP ?');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_USE_SPIP_AUTO','0','yesno','Utilisation de SPIP automatiquement');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_SPIP_USER','user','chaine','user spip');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_SPIP_PASS','pass','chaine','Pass de connection');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_SPIP_SERVEUR','localhost','chaine','serveur spip');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_SPIP_DB','spip','chaine','db spip');
--
-- cartes adherents
--
insert into llx_const(name, value, type, note, visible) values ('ADHERENT_TEXT_NEW_ADH','','texte','Texte d\'entete du formaulaire d\'adhesion en ligne',0);
insert into llx_const(name, value, type, note, visible) values ('ADHERENT_CARD_HEADER_TEXT','%ANNEE%','string','Texte imprime sur le haut de la carte adherent',0);
insert into llx_const(name, value, type, note, visible) values ('ADHERENT_CARD_FOOTER_TEXT','Association FreeLUG http://www.freelug.org/','string','Texte imprime sur le bas de la carte adherent',0);
insert into llx_const(name, value, type, note, visible) values ('ADHERENT_CARD_TEXT','%TYPE% n° %ID%\r\n%PRENOM% %NOM%\r\n<%EMAIL%>\r\n%ADRESSE%\r\n%CP% %VILLE%\r\n%PAYS%','texte','Texte imprime sur la carte adherent',0);
--
-- OsCommerce
--
INSERT INTO llx_const(name, value, type) VALUES ('DB_NAME_OSC','catalog','chaine');
INSERT INTO llx_const(name, value, type) VALUES ('OSC_LANGUAGE_ID','1','chaine');
INSERT INTO llx_const(name, value, type) VALUES ('OSC_CATALOG_URL','http://osc.lafrere.lan/','chaine');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_MAIL_FROM','adherents@domain.com','chaine','From des mails');
INSERT INTO llx_const (name, value, type, note) VALUES ('ADHERENT_MAIL_FROM','adherents@domain.com','chaine','From des mails adherents');
--
-- Menus
--
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_MENU_BARRETOP','default.php','chaine','Module commande');

--
-- Constantes 
--

delete from llx_c_chargesociales;
insert into llx_c_chargesociales (id,libelle,deductible) values ( 1, 'Allocations familiales',1);
insert into llx_c_chargesociales (id,libelle,deductible) values ( 2, 'GSG Deductible',1);
insert into llx_c_chargesociales (id,libelle,deductible) values ( 3, 'GSG/CRDS NON Deductible',0);


delete from llx_c_actioncomm;
insert into llx_c_actioncomm (id,libelle) values ( 0, '-');
insert into llx_c_actioncomm (id,libelle) values ( 1, 'Appel Téléphonique');
insert into llx_c_actioncomm (id,libelle) values ( 2, 'Envoi Fax');
insert into llx_c_actioncomm (id,libelle) values ( 3, 'Envoi propal par mail');
insert into llx_c_actioncomm (id,libelle) values ( 4, 'Envoi d\'un email'); 
insert into llx_c_actioncomm (id,libelle) values ( 5, 'Rendez-vous'); 
insert into llx_c_actioncomm (id,libelle) values ( 9, 'Envoi Facture');
insert into llx_c_actioncomm (id,libelle) values (10, 'Relance effectuée');
insert into llx_c_actioncomm (id,libelle) values (11, 'Clôture');

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
insert into llx_c_pays (id,libelle,code) values (0, 'France',          'FR');
insert into llx_c_pays (id,libelle,code) values (2, 'Belgique',        'BE');
insert into llx_c_pays (id,libelle,code) values (3, 'Italie',          'IT');
insert into llx_c_pays (id,libelle,code) values (4, 'Espagne',         'ES');
insert into llx_c_pays (id,libelle,code) values (5, 'Allemagne',       'DE');
insert into llx_c_pays (id,libelle,code) values (6, 'Suisse',          'CH');
insert into llx_c_pays (id,libelle,code) values (7, 'Royaume uni',     'GB');
insert into llx_c_pays (id,libelle,code) values (8, 'Irlande',         'IE');
insert into llx_c_pays (id,libelle,code) values (9, 'Chine',           'CN');
insert into llx_c_pays (id,libelle,code) values (10, 'Tunisie',        'TN');
insert into llx_c_pays (id,libelle,code) values (11, 'Etats Unis',     'US');
insert into llx_c_pays (id,libelle,code) values (12, 'Maroc',          'MA');
insert into llx_c_pays (id,libelle,code) values (13, 'Algérie',        'DZ');
insert into llx_c_pays (id,libelle,code) values (14, 'Canada',         'CA');
insert into llx_c_pays (id,libelle,code) values (15, 'Togo',           'TG');
insert into llx_c_pays (id,libelle,code) values (16, 'Gabon',          'GA');
insert into llx_c_pays (id,libelle,code) values (17, 'Pays Bas',       'NL');
insert into llx_c_pays (id,libelle,code) values (18, 'Hongrie',        'HU');
insert into llx_c_pays (id,libelle,code) values (19, 'Russie',         'RU');
insert into llx_c_pays (id,libelle,code) values (20, 'Suède',          'SE');
insert into llx_c_pays (id,libelle,code) values (21, 'Côte d\'Ivoire', 'CI');
insert into llx_c_pays (id,libelle,code) values (23, 'Sénégal',        'SN');
insert into llx_c_pays (id,libelle,code) values (24, 'Argentine',      'AR');
insert into llx_c_pays (id,libelle,code) values (25, 'Cameroun',       'CM');

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
insert into llx_c_paiement (id,libelle,type) values (0, '-', 3);
insert into llx_c_paiement (id,libelle,type) values (1, 'TIP', 1);
insert into llx_c_paiement (id,libelle,type) values (2, 'Virement', 2);
insert into llx_c_paiement (id,libelle,type) values (3, 'Prélèvement', 1);
insert into llx_c_paiement (id,libelle,type) values (4, 'Liquide', 0);
insert into llx_c_paiement (id,libelle,type) values (5, 'Paiement en ligne', 0);
insert into llx_c_paiement (id,libelle,type) values (6, 'CB', 1);
insert into llx_c_paiement (id,libelle,type) values (7, 'Chèque', 2);

delete from llx_c_propalst;
insert into llx_c_propalst (id,label) values (0, 'Brouillon');
insert into llx_c_propalst (id,label) values (1, 'Ouverte');
insert into llx_c_propalst (id,label) values (2, 'Signée');
insert into llx_c_propalst (id,label) values (3, 'Non Signée');
insert into llx_c_propalst (id,label) values (4, 'Facturée');
--
-- Formes juridiques
-- Extrait de http://www.insee.fr/fr/nom_def_met/nomenclatures/cj/cjniveau2.htm
insert into llx_c_forme_juridique (code, libelle) values (11,'Artisan Commerçant');
insert into llx_c_forme_juridique (code, libelle) values (12,'Commerçant');
insert into llx_c_forme_juridique (code, libelle) values (13,'Artisan');
insert into llx_c_forme_juridique (code, libelle) values (14,'Officier public ou ministériel');
insert into llx_c_forme_juridique (code, libelle) values (15,'Profession libérale');
insert into llx_c_forme_juridique (code, libelle) values (16,'Exploitant agricole');
insert into llx_c_forme_juridique (code, libelle) values (17,'Agent commercial');
insert into llx_c_forme_juridique (code, libelle) values (18,'Associé Gérant de société');
insert into llx_c_forme_juridique (code, libelle) values (19,'(Autre) personne physique');
insert into llx_c_forme_juridique (code, libelle) values (21,'Indivision');
insert into llx_c_forme_juridique (code, libelle) values (22,'Société créée de fait');
insert into llx_c_forme_juridique (code, libelle) values (23,'Société en participation');
insert into llx_c_forme_juridique (code, libelle) values (27,'Paroisse hors zone concordataire');
insert into llx_c_forme_juridique (code, libelle) values (29,'Autre groupement de droit privé non doté de la personnalité morale');
insert into llx_c_forme_juridique (code, libelle) values (31,'Personne morale de droit étranger, immatriculée au RCS (registre du commerce et des sociétés)');
insert into llx_c_forme_juridique (code, libelle) values (32,'Personne morale de droit étranger, non immatriculée au RCS');

insert into llx_c_forme_juridique (code, libelle) values (41,'Établissement public ou régie à caractère industriel ou commercial');

insert into llx_c_forme_juridique (code, libelle) values (51,'Société coopérative commerciale particulière');
insert into llx_c_forme_juridique (code, libelle) values (52,'Société en nom collectif');
insert into llx_c_forme_juridique (code, libelle) values (53,'Société en commandite');
insert into llx_c_forme_juridique (code, libelle) values (54,'Société à responsabilité limité (SARL)');
insert into llx_c_forme_juridique (code, libelle) values (55,'Société anonyme à conseil d\'administration');
insert into llx_c_forme_juridique (code, libelle) values (56,'Société anonyme à directoire');
insert into llx_c_forme_juridique (code, libelle) values (57,'Société par actions simplifiée');

insert into llx_c_forme_juridique (code, libelle) values (61,'Caisse d\'épargne et de prévoyance');
insert into llx_c_forme_juridique (code, libelle) values (62,'Groupement d\'intérêt économique');
insert into llx_c_forme_juridique (code, libelle) values (63,'Société coopérative agricole');
insert into llx_c_forme_juridique (code, libelle) values (64,'Société non commerciale d\'assurances');
insert into llx_c_forme_juridique (code, libelle) values (65,'Société civile');
insert into llx_c_forme_juridique (code, libelle) values (69,'Autres personnes de droit privé inscrites au registre du commerce et des sociétés');

insert into llx_c_forme_juridique (code, libelle) values (71,'Administration de l\'état');
insert into llx_c_forme_juridique (code, libelle) values (72,'Collectivité territoriale');
insert into llx_c_forme_juridique (code, libelle) values (73,'Établissement public administratif');
insert into llx_c_forme_juridique (code, libelle) values (74,'Autre personne morale de droit public administratif');

insert into llx_c_forme_juridique (code, libelle) values (81,'Organisme gérant un régime de protection social à adhésion obligatoire');
insert into llx_c_forme_juridique (code, libelle) values (82,'Organisme mutualiste');
insert into llx_c_forme_juridique (code, libelle) values (83,'Comité d\'entreprise');
insert into llx_c_forme_juridique (code, libelle) values (84,'Organisme professionnel');
insert into llx_c_forme_juridique (code, libelle) values (85,'Organisme de retraite à adhésion non obligatoire');

insert into llx_c_forme_juridique (code, libelle) values (91,'Syndicat de propriétaires');
insert into llx_c_forme_juridique (code, libelle) values (92,'Association loi 1901 ou assimilé');
insert into llx_c_forme_juridique (code, libelle) values (93,'Fondation');
insert into llx_c_forme_juridique (code, libelle) values (99,'Autre personne morale de droit privé');



