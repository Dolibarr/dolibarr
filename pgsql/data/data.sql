-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
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

--
-- Ne pas place de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

insert into llx_cond_reglement(rowid, code, sortorder, actif, libelle, libelle_facture, fdm, nbjour) values (1,'RECEP',       1,1, 'A réception','Réception de facture',0,0);
insert into llx_cond_reglement(rowid, code, sortorder, actif, libelle, libelle_facture, fdm, nbjour) values (2,'30D',         2,1, '30 jours','Réglement à 30 jours',0,30);
insert into llx_cond_reglement(rowid, code, sortorder, actif, libelle, libelle_facture, fdm, nbjour) values (3,'30DENDMONTH', 3,1, '30 jours fin de mois','Réglement à 30 jours fin de mois',1,30);
insert into llx_cond_reglement(rowid, code, sortorder, actif, libelle, libelle_facture, fdm, nbjour) values (4,'60D',         4,1, '60 jours','Réglement à 60 jours',0,60);
insert into llx_cond_reglement(rowid, code, sortorder, actif, libelle, libelle_facture, fdm, nbjour) values (5,'60DENDMONTH', 5,1, '60 jours fin de mois','Réglement à 60 jours fin de mois',1,60);


insert into llx_sqltables (name, loaded) values ('llx_album',0);

--
-- Définition des actions de workflow notifications
--
delete from llx_action_def;
insert into llx_action_def (rowid,code,titre,description,objet_type) values (1,'NOTIFY_VAL_FICHINTER','Validation fiche intervention','Déclenché lors de la validation d\'une fiche d\'intervention','ficheinter');
insert into llx_action_def (rowid,code,titre,description,objet_type) values (2,'NOTIFY_VAL_FAC','Validation facture','Déclenché lors de la validation d\'une facture','facture');

--
-- Constantes de configuration
--
insert into llx_const (name, value, type, note, visible) values ('MAIN_MONNAIE','euros','chaine','Monnaie',0);

insert into llx_const (name, value, type, note, visible) values ('MAIN_UPLOAD_DOC','2048','chaine','Max size for file upload (0 means no upload allowed)',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_NOT_INSTALLED','1','chaine','Test d\'installation',1);

insert into llx_const (name, value, type, note, visible) values ('MAIN_TITLE','Dolibarr','chaine','Titre des pages',0);


--
-- IHM
--
insert into llx_const (name, value, type, note, visible) values ('MAIN_THEME','eldy','chaine','Thème par défaut',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_SIZE_LISTE_LIMIT','20','chaine','Taille des listes',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_MENU_BARRETOP','default.php','chaine','Module de gestion de la barre de menu du haut',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_LANG_DEFAULT','fr_FR','chaine','Langue par défaut pour les écrans Dolibarr',0);

insert into llx_const (name, value, type, note, visible) values ('MAIN_SEARCHFORM_CONTACT','1','yesno','Affichage formulaire de recherche des Contacts dans la barre de gauche',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_SEARCHFORM_SOCIETE','1','yesno','Affichage formulaire de recherche des Sociétés dans la barre de gauche',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_SEARCHFORM_PRODUITSERVICE','1','yesno','Affichage formulaire de recherche des Produits et Services dans la barre de gauche',0);

--
-- Mail Adherent
--
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_REQUIRED','1','yesno','Le mail est obligatoire pour créer un adhérent',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_FROM','adherents@domain.com','chaine','From des mails adherents',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_RESIL','Votre adhesion sur %SERVEUR% vient d\'etre resilie.\r\nNous esperons vous revoir tres bientot','texte','Mail de Resiliation',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_VALID','MAIN\r\nVotre adhesion vient d\'etre validee. \r\nVoici le rappel de vos coordonnees (toute information erronee entrainera la non validation de votre inscription) :\r\n\r\n%INFO%\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante : \r\n%SERVEUR%public/adherents/','texte','Mail de validation',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_COTIS','Bonjour %PRENOM%,\r\nMerci de votre inscription.\r\nCet email confirme que votre cotisation a ete recue et enregistree.\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante :\r\n%SERVEUR%public/adherents/','texte','Mail de validation de cotisation',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_VALID_SUBJECT','Votre adhésion a ete validée sur %SERVEUR%','chaine','sujet du mail de validation',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_RESIL_SUBJECT','Resiliation de votre adhesion sur %SERVEUR%','chaine','sujet du mail de resiliation',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_COTIS_SUBJECT','Recu de votre cotisation','chaine','sujet du mail de validation de cotisation',0);

--
-- Mail Mailing
--
insert into llx_const (name, value, type, note) values ('MAILING_EMAIL_FROM','mailing@societe.com','chaine','Champ From du mail pour mailing clients/prospects');

--
-- Mailman
--
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_USE_MAILMAN','0','yesno','Utilisation de Mailman',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAILMAN_UNSUB_URL','http://%SERVER%/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%ADMINPW%&user=%EMAIL%','chaine','Url de desinscription aux listes mailman',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAILMAN_URL','http://%SERVER%/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%ADMINPW%&send_welcome_msg_to_this_batch=1&subscribees=%EMAIL%','chaine','Url pour les inscriptions mailman',0);
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
-- Cartes adherents
--
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_CARD_HEADER_TEXT','%ANNEE%','chaine','Texte imprime sur le haut de la carte adherent',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_CARD_FOOTER_TEXT','Association FreeLUG http://www.freelug.org/','chaine','Texte imprime sur le bas de la carte adherent',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_CARD_TEXT','%TYPE% n° %ID%\r\n%PRENOM% %NOM%\r\n<%EMAIL%>\r\n%ADRESSE%\r\n%CP% %VILLE%\r\n%PAYS%','texte','Texte imprime sur la carte adherent',0);

--
-- OsCommerce 1
--
insert into llx_const (name, value, type) values ('OSC_DB_HOST','localhost','chaine');

--
-- Notification
--
insert into llx_const (name, value, type, note, visible) values ('NOTIFICATION_EMAIL_FROM','dolibarr-robot@domain.com','chaine','EMail emetteur pour les notifications automatiques Dolibarr',1);

--
--
--
insert into llx_const (name, value, type, visible) values ('FACTURE_ADDON',       'jupiter','chaine',0);
insert into llx_const (name, value, type, visible) values ('FACTURE_ADDON_PDF',   'crabe','chaine',0);
insert into llx_const (name, value, type, visible) values ('COMMANDE_ADDON',      'mod_commande_ivoire','chaine',0);
insert into llx_const (name, value, type, visible) values ('EXPEDITION_ADDON_PDF','rouget','chaine',0);
insert into llx_const (name, value, type, visible) values ('PROPALE_ADDON',       'mod_propale_ivoire','chaine',0);
insert into llx_const (name, value, type, visible) values ('FACTURE_ADDON_PDF',    'azur','chaine',0);

--
-- Forcer les locales
--
insert into llx_const (name, value, type, visible, note) VALUES ('MAIN_FORCE_SETLOCALE_LC_ALL',      'MAIN_FORCE_SETLOCALE_LC_ALL', 'chaine', 1, 'Pour forcer LC_ALL si pb de locale');
insert into llx_const (name, value, type, visible, note) VALUES ('MAIN_FORCE_SETLOCALE_LC_TIME',     'MAIN_FORCE_SETLOCALE_LC_TIME', 'chaine', 1, 'Pour forcer LC_TIME si pb de locale');
insert into llx_const (name, value, type, visible, note) VALUES ('MAIN_FORCE_SETLOCALE_LC_MONETARY', 'MAIN_FORCE_SETLOCALE_LC_MONETARY', 'chaine', 1, 'Pour forcer LC_MONETARY si pb de locale');
insert into llx_const (name, value, type, visible, note) VALUES ('MAIN_FORCE_SETLOCALE_LC_NUMERIC',  'MAIN_FORCE_SETLOCALE_LC_NUMERIC', 'chaine', 1, 'Mettre la valeur C si problème de centimes');

-- Dictionnaires llx_c

--
-- Descriptif du plan comptable FR PCG99-ABREGE
--

delete from llx_c_accountingsystem;
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES (  1,1,'PCG99-ABREGE','CAPIT', 'CAPITAL',  'Capital','101');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES (  2,1,'PCG99-ABREGE','CAPIT', 'XXXXXX',   'Ecarts de réévaluation','105');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES (  3,1,'PCG99-ABREGE','CAPIT', 'XXXXXX',   'Réserve légale','1061');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES (  4,1,'PCG99-ABREGE','CAPIT', 'XXXXXX',   'Réserves statutaires ou contractuelles','1063');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES (  5,1,'PCG99-ABREGE','CAPIT', 'XXXXXX',   'Réserves réglementées','1064');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES (  6,1,'PCG99-ABREGE','CAPIT', 'XXXXXX',   'Autres réserves','1068');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES (  7,1,'PCG99-ABREGE','CAPIT', 'XXXXXX',   'Compte de l''exploitant','108');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES (  8,1,'PCG99-ABREGE','CAPIT', 'XXXXXX',   'résultat de l''exercice','12');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES (  9,1,'PCG99-ABREGE','CAPIT', 'XXXXXX',   'Amortissements dérogatoires','145');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 10,1,'PCG99-ABREGE','CAPIT', 'XXXXXX',   'Provision spéciale de réévaluation','146');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 11,1,'PCG99-ABREGE','CAPIT', 'XXXXXX',   'Plus-values réinvesties','147');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 12,1,'PCG99-ABREGE','CAPIT', 'XXXXXX',   'Autres provisions réglementées','148');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 13,1,'PCG99-ABREGE','CAPIT', 'XXXXXX',   'Provisions pour risques et charges','15');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 14,1,'PCG99-ABREGE','CAPIT', 'XXXXXX',   'emprunts et dettes assimilees','16');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 15,1,'PCG99-ABREGE','IMMO',  'XXXXXX',   'immobilisations incorporelles','20');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 16,1,'PCG99-ABREGE','IMMO',  'XXXXXX',   'Frais d''établissement','201');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 17,1,'PCG99-ABREGE','IMMO',  'XXXXXX',   'Droit au bail','206');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 18,1,'PCG99-ABREGE','IMMO',  'XXXXXX',   'Fonds commercial','207');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 19,1,'PCG99-ABREGE','IMMO',  'XXXXXX',   'Autres immobilisations incorporelles','208');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 20,1,'PCG99-ABREGE','IMMO',  'XXXXXX',   'immobilisations corporelles','21');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 21,1,'PCG99-ABREGE','IMMO',  'XXXXXX',   'immobilisations en cours','23');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 22,1,'PCG99-ABREGE','IMMO',  'XXXXXX',   'autres immobilisations financieres','27');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 23,1,'PCG99-ABREGE','IMMO',  'XXXXXX',   'Amortissements des immobilisations incorporelles','280');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 24,1,'PCG99-ABREGE','IMMO',  'XXXXXX',   'Amortissements des immobilisations corporelles','281');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 25,1,'PCG99-ABREGE','IMMO',  'XXXXXX',   'Provisions pour dépréciation des immobilisations incorporelles','290');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 26,1,'PCG99-ABREGE','IMMO',  'XXXXXX',   'Provisions pour dépréciation des immobilisations corporelles','291');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 27,1,'PCG99-ABREGE','IMMO',  'XXXXXX',   'Provisions pour dépréciation des autres immobilisations financières','297');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 28,1,'PCG99-ABREGE','STOCK', 'XXXXXX',   'matieres premières','31');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 29,1,'PCG99-ABREGE','STOCK', 'XXXXXX',   'autres approvisionnements','32');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 30,1,'PCG99-ABREGE','STOCK', 'XXXXXX',   'en-cours de production de biens','33');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 31,1,'PCG99-ABREGE','STOCK', 'XXXXXX',   'en-cours de production de services','34');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 32,1,'PCG99-ABREGE','STOCK', 'XXXXXX',   'stocks de produits','35');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 33,1,'PCG99-ABREGE','STOCK', 'XXXXXX',   'stocks de marchandises','37');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 34,1,'PCG99-ABREGE','STOCK', 'XXXXXX',   'Provisions pour dépréciation des matières premières','391');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 35,1,'PCG99-ABREGE','STOCK', 'XXXXXX',   'Provisions pour dépréciation des autres approvisionnements','392');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 36,1,'PCG99-ABREGE','STOCK', 'XXXXXX',   'Provisions pour dépréciation des en-cours de production de biens','393');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 37,1,'PCG99-ABREGE','STOCK', 'XXXXXX',   'Provisions pour dépréciation des en-cours de production de services','394');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 38,1,'PCG99-ABREGE','STOCK', 'XXXXXX',   'Provisions pour dépréciation des stocks de produits','395');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 39,1,'PCG99-ABREGE','STOCK', 'XXXXXX',   'Provisions pour dépréciation des stocks de marchandises','397');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 40,1,'PCG99-ABREGE','TIERS', 'SUPPLIER', 'Fournisseurs et Comptes rattachés','400');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 41,1,'PCG99-ABREGE','TIERS', 'XXXXXX',   'Fournisseurs débiteurs','409');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 42,1,'PCG99-ABREGE','TIERS', 'CUSTOMER', 'Clients et Comptes rattachés','410');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 43,1,'PCG99-ABREGE','TIERS', 'XXXXXX',   'Clients créditeurs','419');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 44,1,'PCG99-ABREGE','TIERS', 'XXXXXX',   'Personnel','421');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 45,1,'PCG99-ABREGE','TIERS', 'XXXXXX',   'Personnel','428');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 46,1,'PCG99-ABREGE','TIERS', 'XXXXXX',   'Sécurité sociale et autres organismes sociaux','43');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 47,1,'PCG99-ABREGE','TIERS', 'XXXXXX',   'Etat - impôts sur bénéfice','444');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 48,1,'PCG99-ABREGE','TIERS', 'XXXXXX',   'Etat - Taxes sur chiffre affaire','445');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 49,1,'PCG99-ABREGE','TIERS', 'XXXXXX',   'Autres impôts, taxes et versements assimilés','447');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 50,1,'PCG99-ABREGE','TIERS', 'XXXXXX',   'Groupe et associes','45');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 51,1,'PCG99-ABREGE','TIERS', 'XXXXXX',   'Associés','455');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 52,1,'PCG99-ABREGE','TIERS', 'XXXXXX',   'Débiteurs divers et créditeurs divers','46');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 53,1,'PCG99-ABREGE','TIERS', 'XXXXXX',   'comptes transitoires ou d''attente','47');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 54,1,'PCG99-ABREGE','TIERS', 'XXXXXX',   'Charges à répartir sur plusieurs exercices','481');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 55,1,'PCG99-ABREGE','TIERS', 'XXXXXX',   'Charges constatées d''avance','486');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 56,1,'PCG99-ABREGE','TIERS', 'XXXXXX',   'Produits constatés d''avance','487');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 57,1,'PCG99-ABREGE','TIERS', 'XXXXXX',   'Provisions pour dépréciation des comptes de clients','491');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 58,1,'PCG99-ABREGE','TIERS', 'XXXXXX',   'Provisions pour dépréciation des comptes de débiteurs divers','496');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 59,1,'PCG99-ABREGE','FINAN', 'XXXXXX',   'valeurs mobilières de placement','50');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 60,1,'PCG99-ABREGE','FINAN', 'BANK',     'banques, établissements financiers et assimilés','51');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 61,1,'PCG99-ABREGE','FINAN', 'CASH',     'Caisse','53');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 62,1,'PCG99-ABREGE','FINAN', 'XXXXXX',   'régies d''avance et accréditifs','54');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 63,1,'PCG99-ABREGE','FINAN', 'XXXXXX',   'virements internes','58');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 64,1,'PCG99-ABREGE','FINAN', 'XXXXXX',   'Provisions pour dépréciation des valeurs mobilières de placement','590');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 65,1,'PCG99-ABREGE','CHARGE','XXXXXX',   'Achats','60');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 66,1,'PCG99-ABREGE','CHARGE','XXXXXX',   'variations des stocks','603');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 67,1,'PCG99-ABREGE','CHARGE','XXXXXX',   'Services extérieurs','61');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 68,1,'PCG99-ABREGE','CHARGE','XXXXXX',   'Autres services extérieurs','62');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 69,1,'PCG99-ABREGE','CHARGE','XXXXXX',   'Impôts, taxes et versements assimiles','63');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 70,1,'PCG99-ABREGE','CHARGE','XXXXXX',   'Rémunérations du personnel','641');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 71,1,'PCG99-ABREGE','CHARGE','XXXXXX',   'Rémunération du travail de l''exploitant','644');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 72,1,'PCG99-ABREGE','CHARGE','SOCIAL',   'Charges de sécurité sociale et de prévoyance','645');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 73,1,'PCG99-ABREGE','CHARGE','XXXXXX',   'Cotisations sociales personnelles de l''exploitant','646');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 74,1,'PCG99-ABREGE','CHARGE','XXXXXX',   'Autres charges de gestion courante','65');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 75,1,'PCG99-ABREGE','CHARGE','XXXXXX',   'Charges financières','66');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 76,1,'PCG99-ABREGE','CHARGE','XXXXXX',   'Charges exceptionnelles','67');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 77,1,'PCG99-ABREGE','CHARGE','XXXXXX',   'Dotations aux amortissements et aux provisions','681');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 78,1,'PCG99-ABREGE','CHARGE','XXXXXX',   'Dotations aux amortissements et aux provisions','686');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 79,1,'PCG99-ABREGE','CHARGE','XXXXXX',   'Dotations aux amortissements et aux provisions','687');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 80,1,'PCG99-ABREGE','CHARGE','XXXXXX',   'Participation des salariés aux résultats','691');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 81,1,'PCG99-ABREGE','CHARGE','XXXXXX',   'Impôts sur les bénéfices','695');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 82,1,'PCG99-ABREGE','CHARGE','XXXXXX',   'Imposition forfaitaire annuelle des sociétés','697');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 83,1,'PCG99-ABREGE','CHARGE','XXXXXX',   'Produits','699');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 84,1,'PCG99-ABREGE','PROD',  'PRODUCT',  'Ventes de produits finis','701');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 85,1,'PCG99-ABREGE','PROD',  'SERVICE',  'Prestations de services','706');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 86,1,'PCG99-ABREGE','PROD',  'PRODUCT',  'Ventes de marchandises','707');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 87,1,'PCG99-ABREGE','PROD',  'PRODUCT',  'Produits des activités annexes','708');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 88,1,'PCG99-ABREGE','PROD',  'XXXXXX',   'Rabais, remises et ristournes accordés par l''entreprise','709');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 89,1,'PCG99-ABREGE','PROD',  'XXXXXX',   'Variation des stocks','713');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 90,1,'PCG99-ABREGE','PROD',  'XXXXXX',   'Production immobilisée','72');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 91,1,'PCG99-ABREGE','PROD',  'XXXXXX',   'Produits nets partiels sur opérations à long terme','73');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 92,1,'PCG99-ABREGE','PROD',  'XXXXXX',   'Subventions d''exploitation','74');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 93,1,'PCG99-ABREGE','PROD',  'XXXXXX',   'Autres produits de gestion courante','75');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 94,1,'PCG99-ABREGE','PROD',  'XXXXXX',   'Jetons de présence et rémunérations d''administrateurs, gérants,...','753');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 95,1,'PCG99-ABREGE','PROD',  'XXXXXX',   'Ristournes perçues des coopératives','754');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 96,1,'PCG99-ABREGE','PROD',  'XXXXXX',   'Quotes-parts de résultat sur opérations faites en commun','755');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 97,1,'PCG99-ABREGE','PROD',  'XXXXXX',   'Produits financiers','76');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 98,1,'PCG99-ABREGE','PROD',  'XXXXXX',   'Produits exceptionnels','77');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES ( 99,1,'PCG99-ABREGE','PROD',  'XXXXXX',   'Reprises sur amortissements et provisions','781');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES (100,1,'PCG99-ABREGE','PROD',  'XXXXXX',   'Reprises sur provisions pour risques','786');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES (101,1,'PCG99-ABREGE','PROD',  'XXXXXX',   'Reprises sur provisions','787');
insert into llx_c_accountingsystem (rowid, fk_pays, pcg_version, pcg_type, pcg_subtype, label, account_number) VALUES (102,1,'PCG99-ABREGE','PROD',  'XXXXXX',   'Transferts de charges','79');

--
-- Types action comm
--

delete from llx_c_actioncomm;
insert into llx_c_actioncomm (id, code, type, libelle) values ( 1, 'AC_TEL',  'system', 'Appel Téléphonique');
insert into llx_c_actioncomm (id, code, type, libelle) values ( 2, 'AC_FAX',  'system', 'Envoi Fax');
insert into llx_c_actioncomm (id, code, type, libelle) values ( 3, 'AC_PROP', 'system', 'Envoi Proposition');
insert into llx_c_actioncomm (id, code, type, libelle) values ( 4, 'AC_EMAIL','system', 'Envoi Email');
insert into llx_c_actioncomm (id, code, type, libelle) values ( 5, 'AC_RDV',  'system', 'Prendre rendez-vous');
insert into llx_c_actioncomm (id, code, type, libelle) values ( 9, 'AC_FAC',  'system', 'Envoi Facture');
insert into llx_c_actioncomm (id, code, type, libelle) values (10, 'AC_REL',  'system', 'Relance effectuée');
insert into llx_c_actioncomm (id, code, type, libelle) values (11, 'AC_CLO',  'system', 'Clôture');

--
-- Ape
--
delete from llx_c_ape;


--
-- Types de charges
--

insert into llx_c_chargesociales (id,libelle,deductible) values ( 1, 'Allocations familiales',1);
insert into llx_c_chargesociales (id,libelle,deductible) values ( 2, 'GSG Deductible',1);
insert into llx_c_chargesociales (id,libelle,deductible) values ( 3, 'GSG/CRDS NON Deductible',0);

--
-- Civilites
--

delete from llx_c_civilite;
insert into llx_c_civilite (rowid, code, civilite, active) values (1 , 'MME',  'Madame', 1);
insert into llx_c_civilite (rowid, code, civilite, active) values (3 , 'MR',   'Monsieur', 1);
insert into llx_c_civilite (rowid, code, civilite, active) values (5 , 'MLE',  'Mademoiselle', 1);
insert into llx_c_civilite (rowid, code, civilite, active) values (7 , 'MTRE', 'Maître', 1);

--
-- Departements/Cantons/Provinces
--

insert into llx_c_departements (rowid, fk_region, code_departement,cheflieu,tncc,ncc,nom) values (0,0,'0','0',0,'-','-');
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
-- Types effectifs
--

delete from llx_c_effectif;
insert into llx_c_effectif (id,code,libelle) values (0, 'EF0',       '-');
insert into llx_c_effectif (id,code,libelle) values (1, 'EF1-5',     '1 - 5');
insert into llx_c_effectif (id,code,libelle) values (2, 'EF6-10',    '6 - 10');
insert into llx_c_effectif (id,code,libelle) values (3, 'EF11-50',   '11 - 50');
insert into llx_c_effectif (id,code,libelle) values (4, 'EF51-100',  '51 - 100');
insert into llx_c_effectif (id,code,libelle) values (5, 'EF100-500', '100 - 500');
insert into llx_c_effectif (id,code,libelle) values (6, 'EF500-',    '> 500');

--
-- Formes juridiques
--

delete from llx_c_forme_juridique;

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (0, '0','-');

-- Pour la France: Extrait de http://www.insee.fr/fr/nom_def_met/nomenclatures/cj/cjniveau2.htm
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'11','Artisan Commerçant');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'12','Commerçant');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'13','Artisan');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'14','Officier public ou ministériel');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'15','Profession libérale');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'16','Exploitant agricole');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'17','Agent commercial');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'18','Associé Gérant de société');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'19','(Autre) personne physique');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'21','Indivision');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'22','Société créée de fait');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'23','Société en participation');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'27','Paroisse hors zone concordataire');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'29','Autre groupement de droit privé non doté de la personnalité morale');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'31','Personne morale de droit étranger, immatriculée au RCS');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'32','Personne morale de droit étranger, non immatriculée au RCS');
                                                                     
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'41','Établissement public ou régie à caractère industriel ou commercial');
                                                                     
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'51','Société coopérative commerciale particulière');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'52','Société en nom collectif');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'53','Société en commandite');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'54','Société à responsabilité limité (SARL)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'55','Société anonyme à conseil d\'administration');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'56','Société anonyme à directoire');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'57','Société par actions simplifiée');
                                                                     
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'61','Caisse d\'épargne et de prévoyance');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'62','Groupement d\'intérêt économique');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'63','Société coopérative agricole');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'64','Société non commerciale d\'assurances');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'65','Société civile');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'69','Autres personnes de droit privé inscrites au registre du commerce et des sociétés');
                                                                     
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'71','Administration de l\'état');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'72','Collectivité territoriale');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'73','Établissement public administratif');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'74','Autre personne morale de droit public administratif');
                                                                     
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'81','Organisme gérant un régime de protection social à adhésion obligatoire');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'82','Organisme mutualiste');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'83','Comité d\'entreprise');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'84','Organisme professionnel');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'85','Organisme de retraite à adhésion non obligatoire');
                                                                     
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'91','Syndicat de propriétaires');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'92','Association loi 1901 ou assimilé');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'93','Fondation');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'99','Autre personne morale de droit privé');

--
-- Pour la Belgique
--

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '200', 'Indépendant');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '201', 'SPRL - Société à responsabilité limitée');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '202', 'SA   - Société Anonyme');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '203', 'SCRL - Société coopérative à responsabilité limitée');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '204', 'ASBL - Association sans but Lucratif');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '205', 'SCRI - Société coopérative à responsabilité illimitée');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '206', 'SCS  - Société en commandite simple');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '207', 'SCA  - Société en commandite par action');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '208', 'SNC  - Société en nom collectif');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '209', 'GIE  - Groupement d\'intérêt économique');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '210', 'GEIE - Groupement européen d\'intérêt économique');

--
-- Pour la Suisse
--

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '600', 'Raison Individuelle');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '601', 'Société Simple');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '602', 'Société en nom collectif');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '603', 'Société en commandite');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '604', 'Société anonyme (SA)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '605', 'Société en commandite par actions');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '606', 'Société à responsabilité limitées (SARL)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '607', 'Société coopérative');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '608', 'Association');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '609', 'Fondation');

--
-- Pour le Royaume Uni
--

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (7, '700', 'Sole Trader');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (7, '701', 'Partnership');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (7, '702', 'Private Limited Company by shares - (LTD)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (7, '703', 'Public Limited Company');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (7, '704', 'Workers Cooperative');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (7, '705', 'Limited Liability Partnership');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (7, '706', 'Franchise');

--
-- Types paiement
--

delete from llx_c_paiement;
insert into llx_c_paiement (id,code,libelle,type,active) values (0, '',    '-',                 3,1);
insert into llx_c_paiement (id,code,libelle,type,active) values (1, 'TIP', 'TIP',               2,1);
insert into llx_c_paiement (id,code,libelle,type,active) values (2, 'VIR', 'Virement',          2,1);
insert into llx_c_paiement (id,code,libelle,type,active) values (3, 'PRE', 'Prélèvement',       2,1);
insert into llx_c_paiement (id,code,libelle,type,active) values (4, 'LIQ', 'Espèces',           2,1);
insert into llx_c_paiement (id,code,libelle,type,active) values (5, 'VAD', 'Paiement en ligne', 2,0);
insert into llx_c_paiement (id,code,libelle,type,active) values (6, 'CB',  'Carte Bancaire',    2,1);
insert into llx_c_paiement (id,code,libelle,type,active) values (7, 'CHQ', 'Chèque',            2,1);
insert into llx_c_paiement (id,code,libelle,type,active) values (8, 'TRA', 'Traite',            2,1);
insert into llx_c_paiement (id,code,libelle,type,active) values (9, 'LCR', 'LCR',               2,1);
insert into llx_c_paiement (id,code,libelle,type,active) values (10,'FAC', 'Factor',            2,1);
insert into llx_c_paiement (id,code,libelle,type,active) values (11,'PRO', 'Proforma',          2,1);

--
-- Pays
--

delete from llx_c_pays;
insert into llx_c_pays (rowid,code,libelle) values (0,  ''  , '-'              );
insert into llx_c_pays (rowid,code,libelle) values (1,  'FR', 'France'         );
insert into llx_c_pays (rowid,code,libelle) values (2,  'BE', 'Belgique'       );
insert into llx_c_pays (rowid,code,libelle) values (3,  'IT', 'Italie'         );
insert into llx_c_pays (rowid,code,libelle) values (4,  'ES', 'Espagne'        );
insert into llx_c_pays (rowid,code,libelle) values (5,  'DE', 'Allemagne'      );
insert into llx_c_pays (rowid,code,libelle) values (6,  'CH', 'Suisse'         );
insert into llx_c_pays (rowid,code,libelle) values (7,  'GB', 'Royaume uni'    );
insert into llx_c_pays (rowid,code,libelle) values (8,  'IE', 'Irlande'        );
insert into llx_c_pays (rowid,code,libelle) values (9,  'CN', 'Chine'          );
insert into llx_c_pays (rowid,code,libelle) values (10, 'TN', 'Tunisie'        );
insert into llx_c_pays (rowid,code,libelle) values (11, 'US', 'Etats Unis'     );
insert into llx_c_pays (rowid,code,libelle) values (12, 'MA', 'Maroc'          );
insert into llx_c_pays (rowid,code,libelle) values (13, 'DZ', 'Algérie'        );
insert into llx_c_pays (rowid,code,libelle) values (14, 'CA', 'Canada'         );
insert into llx_c_pays (rowid,code,libelle) values (15, 'TG', 'Togo'           );
insert into llx_c_pays (rowid,code,libelle) values (16, 'GA', 'Gabon'          );
insert into llx_c_pays (rowid,code,libelle) values (17, 'NL', 'Pays Bas'       );
insert into llx_c_pays (rowid,code,libelle) values (18, 'HU', 'Hongrie'        );
insert into llx_c_pays (rowid,code,libelle) values (19, 'RU', 'Russie'         );
insert into llx_c_pays (rowid,code,libelle) values (20, 'SE', 'Suède'          );
insert into llx_c_pays (rowid,code,libelle) values (21, 'CI', 'Côte d\'Ivoire' );
insert into llx_c_pays (rowid,code,libelle) values (23, 'SN', 'Sénégal'        );
insert into llx_c_pays (rowid,code,libelle) values (24, 'AR', 'Argentine'      );
insert into llx_c_pays (rowid,code,libelle) values (25, 'CM', 'Cameroun'       );
insert into llx_c_pays (rowid,code,libelle) values (26, 'PT', 'Portugal'       );
insert into llx_c_pays (rowid,code,libelle) values (27, 'SA', 'Arabie Saoudite');
insert into llx_c_pays (rowid,code,libelle) values (28, 'MC', 'Monaco'         );

--
-- Types etat propales
--

delete from llx_c_propalst;
insert into llx_c_propalst (id,code,label) values (0, 'PR_DRAFT',     'Brouillon');
insert into llx_c_propalst (id,code,label) values (1, 'PR_OPEN',      'Ouverte');
insert into llx_c_propalst (id,code,label) values (2, 'PR_SIGNED',    'Signée');
insert into llx_c_propalst (id,code,label) values (3, 'PR_NOTSIGNED', 'Non Signée');
insert into llx_c_propalst (id,code,label) values (4, 'PR_FAC',       'Facturée');

--
-- Types action st
--

delete from llx_c_stcomm;
insert into llx_c_stcomm (id,code,libelle) values (-1, 'ST_NO',    'Ne pas contacter');
insert into llx_c_stcomm (id,code,libelle) values ( 0, 'ST_NEVER', 'Jamais contacté');
insert into llx_c_stcomm (id,code,libelle) values ( 1, 'ST_TODO',  'A contacter');
insert into llx_c_stcomm (id,code,libelle) values ( 2, 'ST_PEND',  'Contact en cours');
insert into llx_c_stcomm (id,code,libelle) values ( 3, 'ST_DONE',  'Contactée');

--
-- Types entreprises
--

delete from llx_c_typent;
insert into llx_c_typent (id,code,libelle) values (  0, 'TE_UNKNOWN', '-');
insert into llx_c_typent (id,code,libelle) values (  1, 'TE_STARTUP', 'Start-up');
insert into llx_c_typent (id,code,libelle) values (  2, 'TE_GROUP',   'Grand groupe');
insert into llx_c_typent (id,code,libelle) values (  3, 'TE_MEDIUM',  'PME/PMI');
insert into llx_c_typent (id,code,libelle) values (  4, 'TE_SMALL',   'TPE');
insert into llx_c_typent (id,code,libelle) values (  5, 'TE_ADMIN',   'Administration');
insert into llx_c_typent (id,code,libelle) values (  6, 'TE_WHOLE',   'Grossiste');
insert into llx_c_typent (id,code,libelle) values (  7, 'TE_RETAIL',  'Revendeur');
insert into llx_c_typent (id,code,libelle) values (  8, 'TE_PRIVATE', 'Particulier');
insert into llx_c_typent (id,code,libelle) values (100, 'TE_OTHER',   'Autres');

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
-- Devises (code secondaire - code ISO4217 - libelle fr)
--

insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'BT', 'THB', 1, 'Bath thailandais'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'CD', 'DKK', 1, 'Couronnes dannoises'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'CN', 'NOK', 1, 'Couronnes norvegiennes'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'CS', 'SEK', 1, 'Couronnes suedoises'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'CZ', 'CZK', 1, 'Couronnes tcheques'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'DA', 'AUD', 1, 'Dollars australiens'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'DC', 'CAD', 1, 'Dollars canadiens'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'DH', 'HKD', 1, 'Dollars hong kong'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'DS', 'SGD', 1, 'Dollars singapour'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'DU', 'USD', 1, 'Dollars us'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'EC', 'XEU', 1, 'Ecus'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'ES', 'PTE', 1, 'Escudos'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'FB', 'BEF', 1, 'Francs belges'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'FF', 'FRF', 1, 'Francs francais'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'FL', 'LUF', 1, 'Francs luxembourgeois'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'FO', 'NLG', 1, 'Florins'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'FS', 'CHF', 1, 'Francs suisses'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'LI', 'IEP', 1, 'Livres irlandaises'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'LR', 'ITL', 1, 'Lires'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'LS', 'GBP', 1, 'Livres sterling'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'MA', 'DEM', 1, 'Deutsch mark'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'MF', 'FIM', 1, 'Mark finlandais'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'PA', 'ARP', 1, 'Pesos argentins'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'PC', 'CLP', 1, 'Pesos chilien'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'PE', 'ESP', 1, 'Pesete'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'PL', 'PLN', 1, 'Zlotys polonais'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'SA', 'ATS', 1, 'Shiliing autrichiens'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'TW', 'TWD', 1, 'Dollar taiwanais'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'YE', 'JPY', 1, 'Yens'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'ZA', 'ZAR', 1, 'Rand africa'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'DR', 'GRD', 1, 'Drachme (grece)'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'EU', 'EUR', 1, 'Euros'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'RB', 'BRL', 1, 'Real bresilien'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'SK', 'SKK', 1, 'Couronnes slovaques'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'YC', 'CNY', 1, 'Yuang chinois'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'AE', 'AED', 1, 'Arabes emirats dirham'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'CF', 'XAF', 1, 'Francs cfa beac'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'EG', 'EGP', 1, 'Livre egyptienne'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'KR', 'KRW', 1, 'Won coree du sud'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'NZ', 'NZD', 1, 'Dollar neo-zelandais'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'TR', 'TRL', 1, 'Livre turque'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'ID', 'IDR', 1, 'Rupiahs d''indonesie'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'IN', 'INR', 1, 'Roupie indienne'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'LT', 'LTL', 1, 'Litas'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'RU', 'SUR', 1, 'Rouble'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'FH', 'HUF', 1, 'Forint hongrois'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'LK', 'LKR', 1, 'Roupie sri lanka');

--
-- Taux TVA
-- Source des taux: http://fr.wikipedia.org/wiki/Taxe_sur_la_valeur_ajout%C3%A9e
--

delete from llx_c_tva;

-- ALLEMAGNE (id 5)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 51, 5,  '16','0','VAT Rate 16',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 52, 5,   '7','0','VAT Rate 7',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 53, 5,   '0','0','VAT Rate 0',1);

-- BELGIQUE (id 2)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 21, 2,  '21','0','VAT Rate 21',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 22, 2,   '6','0','VAT Rate 6',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 23, 2,   '0','0','VAT Rate 0 ou non applicable',1);

-- CANADA (id 14)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (141,14,   '7','0','VAT Rate 7',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (142,14,   '0','0','VAT Rate 0',1);

-- ESPAGNE (id 4)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 41, 4,  '16','0','VAT Rate 16',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 42, 4,   '7','0','VAT Rate 7',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 43, 4,   '4','0','VAT Rate 4',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 44, 4,   '0','0','VAT Rate 0',1);

-- ITALY (id 3)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 31, 3,  '20','0','VAT Rate 20',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 32, 3,  '10','0','VAT Rate 10',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 33, 3,   '4','0','VAT Rate 4',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 34, 3,   '0','0','VAT Rate 0',1);

-- FRANCE (id 1)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 11, 1,'19.6','0','VAT Rate 19.6 (France hors DOM-TOM)',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 12, 1, '8.5','0','VAT Rate 8.5 (DOM sauf Guyane et Saint-Martin)',0);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 13, 1, '8.5','1','VAT Rate 8.5 (DOM sauf Guyane et Saint-Martin), non perçu par le vendeur mais récupérable par l\'acheteur',0);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 14, 1, '5.5','0','VAT Rate 5.5 (France hors DOM-TOM)',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 15, 1,   '0','0','VAT Rate 0 ou non applicable (France, TOM)',1);

-- PAYS-BAS (id 17)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (171,17,  '19','0','VAT Rate 19',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (172,17,   '6','0','VAT Rate 6',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (173,17,   '0','0','VAT Rate 0',1);

-- PORTUGAL (id 26)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (261,26,  '17','0','VAT Rate 17',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (262,26,  '12','0','VAT Rate 12',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (263,26,   '0','0','VAT Rate 0',1);

-- ROYAUME UNI (id 7)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 71, 7,'17.5','0','VAT Rate 17.5',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 72, 7,   '5','0','VAT Rate 5',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 73, 7,   '0','0','VAT Rate 0',1);

-- SUISSE (id 6)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 61, 6, '7.6','0','VAT Rate 7.6',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 62, 6, '3.6','0','VAT Rate 3.6',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 63, 6, '2.4','0','VAT Rate 2.4',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 64, 6,   '0','0','VAT Rate 0',1);



--
-- Les types de contact d'un element
--
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (10, 'contrat', 'internal', 'SALESREPSIGN',  'Commercial signataire du contrat', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (11, 'contrat', 'internal', 'SALESREPFOLL',  'Commercial suivi du contrat', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (20, 'contrat', 'external', 'BILLING',       'Contact client facturation contrat', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (21, 'contrat', 'external', 'CUSTOMER',      'Contact client suivi contrat', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (22, 'contrat', 'external', 'SALESREPSIGN',  'Contact client signataire contrat', 1);
                                                                                                    
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (30, 'propal',  'internal', 'SALESREPSIGN',  'Commercial signataire de la propale', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (31, 'propal',  'internal', 'SALESREPFOLL',  'Commercial suivi de la propale', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (40, 'propal',  'external', 'BILLING',       'Contact client facturation propale', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (41, 'propal',  'external', 'CUSTOMER',      'Contact client suivi propale', 1);
                                                                                                    
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (80, 'projet',  'internal', 'PROJECTLEADER', 'Chef de Projet', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (81, 'projet',  'external', 'PROJECTLEADER', 'Chef de Projet', 1);

insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (50, 'facture', 'internal', 'SALESREPFOLL',  'Commercial suivi du paiement', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (60, 'facture', 'external', 'BILLING',       'Contact client facturation', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (61, 'facture', 'external', 'SHIPPING',      'Contact client livraison', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (62, 'facture', 'external', 'SERVICE',       'Contact client prestation', 1);