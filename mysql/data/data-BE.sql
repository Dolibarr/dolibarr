-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004      Laurent Destailleuro <eldy@users.sourceforge.net>
-- Copyright (C) 2004	   Benoit Mortier <benoit.mortier@opensides.be>
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
-- Valeurs pour les bases de langues francaises version belge
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
insert into llx_const (name, value, type, note) values ('MAIN_MAIL_FROM','adherents@domain.com','chaine','From des mails');

insert into llx_const (name, value, type, note) values ('MAIN_START_YEAR','2004','chaine','Année de départ');

insert into llx_const (name, value, type, note) values ('MAIN_TITLE','Dolibarr','chaine','Titre des pages');
insert into llx_const (name, value, type, note) values ('MAIN_DEBUG','1','yesno','Debug ..');

insert into llx_const (name, value, type, note) values ('MAIN_SEARCHFORM_SOCIETE','1','yesno','Affichage du formulaire de recherche des sociétés dans la barre de gauche');
insert into llx_const (name, value, type, note) values ('MAIN_SEARCHFORM_CONTACT','1','yesno','Affichage du formulaire de recherche des contacts dans la barre de gauche');

insert into llx_const (name, value, type, note, visible) values ('COMPTA_BANK_FACTURES','1','yesno','Menu factures dans la partie bank',0);
insert into llx_const (name, value, type, note, visible) values ('COMPTA_ONLINE_PAYMENT_BPLC','0','yesno','Système de gestion de la banque populaire de Lorraine',0);

--
-- IHM
--
insert into llx_const (name, value, type, note, visible) values ('MAIN_THEME','yellow','chaine','Thème par défaut',0);
insert into llx_const (name, value, type, note, visible) values ('SIZE_LISTE_LIMIT','20','chaine','Taille des listes',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_MENU_BARRETOP','default.php','chaine','Module de gestion de la barre de menu du haut',0);

--
-- Dons
--
insert into llx_const(name, value, type) values ('DONS_FORM','fsfe.fr.php','chaine');

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
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAILMAN_ADMINPW','','string','Mot de passe Admin des liste mailman',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAILMAN_SERVER','lists.domain.com','string','Serveur hebergeant les interfaces d\'Admin des listes mailman',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAILMAN_LISTS_COTISANT','','string','Liste(s) auxquelles les nouveaux cotisants sont inscris automatiquement',0);
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
-- Cartes adherents
--
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_TEXT_NEW_ADH','','texte','Texte d\'entete du formaulaire d\'adhesion en ligne',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_CARD_HEADER_TEXT','%ANNEE%','string','Texte imprime sur le haut de la carte adherent',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_CARD_FOOTER_TEXT','Association FreeLUG http://www.freelug.org/','string','Texte imprime sur le bas de la carte adherent',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_CARD_TEXT','%TYPE% n° %ID%\r\n%PRENOM% %NOM%\r\n<%EMAIL%>\r\n%ADRESSE%\r\n%CP% %VILLE%\r\n%PAYS%','texte','Texte imprime sur la carte adherent',0);

--
-- OsCommerce
--
insert into llx_const (name, value, type) values ('DB_NAME_OSC','catalog','chaine');
insert into llx_const (name, value, type) values ('OSC_LANGUAGE_ID','1','chaine');
insert into llx_const (name, value, type) values ('OSC_CATALOG_URL','http://osc.lafrere.lan/','chaine');

--
-- Types de charges
--
delete from llx_c_chargesociales;
insert into llx_c_chargesociales (id,libelle,deductible) values ( 1, 'Allocations familiales',1);
--insert into llx_c_chargesociales (id,libelle,deductible) values ( 2, 'GSG Deductible',1);
--insert into llx_c_chargesociales (id,libelle,deductible) values ( 3, 'GSG/CRDS NON Deductible',0);

--
-- Types action
--
delete from llx_c_actioncomm;
insert into llx_c_actioncomm (id,libelle) values ( 0, '-');
insert into llx_c_actioncomm (id,libelle) values ( 1, 'Appel Téléphonique');
insert into llx_c_actioncomm (id,libelle) values ( 2, 'Envoi Fax');
insert into llx_c_actioncomm (id,libelle) values ( 3, 'Envoi proposition par mail');
insert into llx_c_actioncomm (id,libelle) values ( 4, 'Envoi d\'un email'); 
insert into llx_c_actioncomm (id,libelle) values ( 5, 'Rendez-vous'); 
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
-- Types entreprise
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
-- Régions
--
delete from llx_c_regions;
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (0,0,0,'0',0,'-');
insert into llx_c_regions (fk_pays,code_region,cheflieu,tncc,nom) values (2,01,'97105',1,'Flandre');
insert into llx_c_regions (fk_pays,code_region,cheflieu,tncc,nom) values (2,02,'97209',2,'Wallonie');
insert into llx_c_regions (fk_pays,code_region,cheflieu,tncc,nom) values (2,03,'97302',3,'Bruxelles-Capitale');

--
-- Provinces
--
delete from llx_c_departements;
insert into llx_c_departements (rowid, fk_region, code_departement,cheflieu,tncc,ncc,nom) values (0,0,0,'0',0,'-','-');

insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'01','01053',1,'ANVERS','Anvers');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'02','02408',3,'BRUXELLES-CAPITALE','Bruxelles-Capitale');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (22,'03','03190',2,'BRABANT-WALLON','Brabant-Wallon');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (83,'04','04070',1,'BRABANT-FLAMAND','Brabant-Flamand');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'05','05061',1,'FLANDRE-OCCIDENTALE','Flandre-Occidentale');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'06','06088',1,'FLANDRE-ORIENTALE','Flandre-Orientale');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'07','07186',2,'HAINAUT','Hainaut');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'08','08105',2,'LIEGE','Liège');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (21,'09','09105',1,'LIMBOURG','Limbourg');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) VALUES (73,'10','10387',2,'LUXEMBOURG','Luxembourg');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) VALUES (21,'11','11069',2,'NAMUR','Namur');

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
insert into llx_c_paiement (id,libelle,type) values (6, 'Cartes de crédit', 1);
insert into llx_c_paiement (id,libelle,type) values (7, 'Chèques', 2);

delete from llx_c_propalst;
insert into llx_c_propalst (id,label) values (0, 'Brouillon');
insert into llx_c_propalst (id,label) values (1, 'Ouverte');
insert into llx_c_propalst (id,label) values (2, 'Signée');
insert into llx_c_propalst (id,label) values (3, 'Non Signée');
insert into llx_c_propalst (id,label) values (4, 'Facturée');

--
-- Formes juridiques Belges
--
insert into llx_c_forme_juridique (code, libelle) values (0,'Non renseignée');

insert into llx_c_forme_juridique (code, libelle) values (1,'Indépendant');
insert into llx_c_forme_juridique (code, libelle) values (2,'SC - Coopérative');
insert into llx_c_forme_juridique (code, libelle) values (2,'SCRL - Coopérative à responsabilité limitée');
insert into llx_c_forme_juridique (code, libelle) values (3,'SPRL - Société à responsabilité limitée');
insert into llx_c_forme_juridique (code, libelle) values (4,'SA - Société Anonyme');
insert into llx_c_forme_juridique (code, libelle) values (52,'Société en nom collectif');
insert into llx_c_forme_juridique (code, libelle) values (53,'Société en commandite');
insert into llx_c_forme_juridique (code, libelle) values (71,'Administration publique');
insert into llx_c_forme_juridique (code, libelle) values (91,'Syndicat de propriétaires');
insert into llx_c_forme_juridique (code, libelle) values (93,'Fondations');
