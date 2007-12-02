-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
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


INSERT INTO llx_c_methode_commande_fournisseur (rowid, code, libelle, active) VALUES (1, 'OrderByMail',  'Courrier',  1);
INSERT INTO llx_c_methode_commande_fournisseur (rowid, code, libelle, active) VALUES (2, 'OrderByFax',   'Fax',       1);
INSERT INTO llx_c_methode_commande_fournisseur (rowid, code, libelle, active) VALUES (3, 'OrderByEMail', 'EMail',     1);
INSERT INTO llx_c_methode_commande_fournisseur (rowid, code, libelle, active) VALUES (4, 'OrderByPhone', 'Téléphone', 1);
INSERT INTO llx_c_methode_commande_fournisseur (rowid, code, libelle, active) VALUES (5, 'OrderByWWW',   'En ligne',  1);


insert into llx_cond_reglement(rowid, code, sortorder, active, libelle, libelle_facture, fdm, nbjour) values (1,'RECEP',       1,1, 'A réception','Réception de facture',0,0);
insert into llx_cond_reglement(rowid, code, sortorder, active, libelle, libelle_facture, fdm, nbjour) values (2,'30D',         2,1, '30 jours','Réglement à 30 jours',0,30);
insert into llx_cond_reglement(rowid, code, sortorder, active, libelle, libelle_facture, fdm, nbjour) values (3,'30DENDMONTH', 3,1, '30 jours fin de mois','Réglement à 30 jours fin de mois',1,30);
insert into llx_cond_reglement(rowid, code, sortorder, active, libelle, libelle_facture, fdm, nbjour) values (4,'60D',         4,1, '60 jours','Réglement à 60 jours',0,60);
insert into llx_cond_reglement(rowid, code, sortorder, active, libelle, libelle_facture, fdm, nbjour) values (5,'60DENDMONTH', 5,1, '60 jours fin de mois','Réglement à 60 jours fin de mois',1,60);
insert into llx_cond_reglement(rowid, code, sortorder, active, libelle, libelle_facture, fdm, nbjour) values (6,'PROFORMA',    6,1, 'Proforma','Réglement avant livraison',0,0);


--
-- Définition des actions de workflow notifications
--
delete from llx_action_def;
insert into llx_action_def (rowid,code,titre,description,objet_type) values (1,'NOTIFY_VAL_FICHINTER','Validation fiche intervention','Déclenché lors de la validation d\'une fiche d\'intervention','ficheinter');
insert into llx_action_def (rowid,code,titre,description,objet_type) values (2,'NOTIFY_VAL_FAC','Validation facture','Déclenché lors de la validation d\'une facture','facture');
insert into llx_action_def (rowid,code,titre,description,objet_type) values (3,'NOTIFY_VAL_ORDER_SUPPLIER','Validation commande fournisseur','Déclenché lors de la validation d\'une commande fournisseur','order_supplier');

--
-- Constantes de configuration
--
insert into llx_const (name, value, type, note, visible) values ('MAIN_NOT_INSTALLED','1','chaine','Setup is running',1);

insert into llx_const (name, value, type, note, visible) values ('MAIN_MONNAIE','EUR','chaine','Monnaie',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_POPUP_CALENDAR','eldy','chaine','Popup calendar module',0);


insert into llx_const (name, value, type, note, visible) values ('MAIN_MAIL_SMTP_SERVER','','chaine','Host or ip address for SMTP server',1);
insert into llx_const (name, value, type, note, visible) values ('MAIN_MAIL_SMTP_PORT','','chaine','Port for SMTP server',1);
insert into llx_const (name, value, type, note, visible) values ('MAIN_MAIL_EMAIL_FROM','dolibarr-robot@domain.com','chaine','EMail emetteur pour les emails automatiques Dolibarr',1);

insert into llx_const (name, value, type, note, visible) values ('MAIN_UPLOAD_DOC','2048','chaine','Max size for file upload (0 means no upload allowed)',0);

insert into llx_const(name,value,type,visible,note) values('MAIN_FEATURES_LEVEL','0','chaine',1,'Level of features to show (0=stable only, 1=stable+experimental, 2=stable+experimental+development');

insert into llx_const(name,value,type,visible,note) values('MAIN_FASTSEARCH_COMPANY','1','yesno',0,'Show form for quick company search');
insert into llx_const(name,value,type,visible,note) values('MAIN_FASTSEARCH_CONTACT','1','yesno',0,'Show form for quick contact search');
insert into llx_const(name,value,type,visible,note) values('MAIN_FASTSEARCH_PRODUCT','1','yesno',0,'Show form for quick product search');


--
-- IHM
--

insert into llx_const (name, value, type, note, visible) values ('MAIN_SIZE_LISTE_LIMIT','25','chaine','Longueur maximum des listes',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_SHOW_WORKBOARD','1','yesno','Affichage tableau de bord de travail Dolibarr',0);

insert into llx_const (name, value, type, note, visible) values ('MAIN_MENU_BARRETOP','eldy_backoffice.php','chaine','Module de gestion de la barre de menu du haut pour utilisateurs internes',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_MENUFRONT_BARRETOP','eldy_frontoffice.php','chaine','Module de gestion de la barre de menu du haut pour utilisateurs externes',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_MENU_BARRELEFT','eldy_backoffice.php','chaine','Module de gestion de la barre de menu gauche pour utilisateurs internes',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_MENUFRONT_BARRELEFT','eldy_frontoffice.php','chaine','Module de gestion de la barre de menu gauche pour utilisateurs externes',0);

insert into llx_const (name, value, type, note, visible) values ('MAIN_THEME','eldy','chaine','Thème par défaut',0);

--
-- Delai tolerance
--
insert into llx_const (name, value, type, note, visible) values ('MAIN_DELAY_ACTIONS_TODO','7','chaine','Tolérance de retard avant alerte (en jours) sur actions planifiées non réalisées',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_DELAY_ORDERS_TO_PROCESS','2','chaine','Tolérance de retard avant alerte (en jours) sur commandes non traitées',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_DELAY_PROPALS_TO_CLOSE','31','chaine','Tolérance de retard avant alerte (en jours) sur propales à cloturer',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_DELAY_PROPALS_TO_BILL','7','chaine','Tolérance de retard avant alerte (en jours) sur propales non facturées',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_DELAY_SUPPLIER_BILLS_TO_PAY','2','chaine','Tolérance de retard avant alerte (en jours) sur factures fournisseur impayées',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_DELAY_CUSTOMER_BILLS_UNPAYED','31','chaine','Tolérance de retard avant alerte (en jours) sur factures client impayées',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_DELAY_NOT_ACTIVATED_SERVICES','0','chaine','Tolérance de retard avant alerte (en jours) sur services à activer',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_DELAY_RUNNING_SERVICES','0','chaine','Tolérance de retard avant alerte (en jours) sur services expirés',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_DELAY_MEMBERS','31','chaine','Tolérance de retard avant alerte (en jours) sur cotisations adhérent en retard',0);
insert into llx_const (name, value, type, note, visible) values ('MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE','62','chaine','Tolérance de retard avant alerte (en jours) sur rapprochements bancaires à faire',0);


--
-- Tiers
--
insert into llx_const(name,value,type,visible,note) values('SOCIETE_NOLIST_COURRIER','1','yesno',0,'Liste les fichiers du repertoire courrier');


--
-- Facture
--
insert into llx_const(name,value,type,visible,note) values('FACTURE_DISABLE_RECUR','1','yesno',0,'Desactivation facture recurrentes');


--
-- Mail Adherent
--
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_REQUIRED','1','yesno','Le mail est obligatoire pour créer un adhérent',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_FROM','adherents@domain.com','chaine','From des mails adherents',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_RESIL','Votre adhesion vient d\'etre resiliee.\r\nNous esperons vous revoir tres bientot','texte','Mail de Resiliation',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_VALID','Votre adhesion vient d\'etre validee. \r\nVoici le rappel de vos coordonnees (toute information erronee entrainera la non validation de votre inscription) :\r\n\r\n%INFOS%\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante : \r\n%DOL_MAIN_URL_ROOT%/public/adherents/','texte','Mail de validation',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_COTIS','Bonjour %PRENOM%,\r\nMerci de votre inscription.\r\nCet email confirme que votre cotisation a ete recue et enregistree.\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante :\r\n%DOL_MAIN_URL_ROOT%/public/adherents/','texte','Mail de validation de cotisation',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_VALID_SUBJECT','Votre adhésion a ete validée','chaine','Sujet du mail de validation',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_RESIL_SUBJECT','Resiliation de votre adhesion','chaine','Sujet du mail de resiliation',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAIL_COTIS_SUBJECT','Recu de votre cotisation','chaine','Sujet du mail de validation de cotisation',0);


--
-- Mail Mailing
--
insert into llx_const (name, value, type, note, visible) values ('MAILING_EMAIL_FROM','dolibarr@domain.com','chaine','EMail emmetteur pour les envois d\'emailings',0);

--
-- Mailman
--
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_USE_MAILMAN','0','yesno','Utilisation de Mailman',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAILMAN_UNSUB_URL','http://lists.domain.com/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%MAILMAN_ADMINPW%&user=%EMAIL%','chaine','Url de desinscription aux listes mailman',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_MAILMAN_URL','http://lists.domain.com/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%MAILMAN_ADMINPW%&send_welcome_msg_to_this_batch=1&subscribees=%EMAIL%','chaine','Url pour les inscriptions mailman',0);
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
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_CARD_FOOTER_TEXT','Association AZERTY','chaine','Texte imprime sur le bas de la carte adherent',0);
insert into llx_const (name, value, type, note, visible) values ('ADHERENT_CARD_TEXT','%TYPE% n° %ID%\r\n%PRENOM% %NOM%\r\n<%EMAIL%>\r\n%ADRESSE%\r\n%CP% %VILLE%\r\n%PAYS%','texte','Texte imprime sur la carte adherent',0);

--
-- FCKEditor
--
insert into llx_const (name, value, type, note, visible) values ('FCKEDITOR_ENABLE_USER',       1,'yesno','Activation fckeditor sur notes utilisateurs',0);
insert into llx_const (name, value, type, note, visible) values ('FCKEDITOR_ENABLE_SOCIETE',    1,'yesno','Activation fckeditor sur notes societe',0);
insert into llx_const (name, value, type, note, visible) values ('FCKEDITOR_ENABLE_PRODUCTDESC',1,'yesno','Activation fckeditor sur notes produits',0);
insert into llx_const (name, value, type, note, visible) values ('FCKEDITOR_ENABLE_MEMBER',     1,'yesno','Activation fckeditor sur notes adherent',0);
insert into llx_const (name, value, type, note, visible) values ('FCKEDITOR_ENABLE_MAILING',    1,'yesno','Activation fckeditor sur emailing',0);

--
-- OsCommerce 1
--
insert into llx_const (name, value, type, note, visible) values ('OSC_DB_HOST','localhost','chaine', 'Host for OSC database for OSCommerce module 1', 0);


--
-- Modeles de numerotation et generation document
--
insert into llx_const (name, value, type, visible) values ('DON_ADDON_MODEL',     'html_cerfafr','chaine',0);
insert into llx_const (name, value, type, visible) values ('PROPALE_ADDON',       'mod_propale_marbre','chaine',0);
insert into llx_const (name, value, type, visible) values ('PROPALE_ADDON_PDF',   'azur','chaine',0);
insert into llx_const (name, value, type, visible) values ('COMMANDE_ADDON',      'mod_commande_marbre','chaine',0);
insert into llx_const (name, value, type, visible) values ('COMMANDE_ADDON_PDF',  'einstein','chaine',0);
insert into llx_const (name, value, type, visible) values ('COMMANDE_SUPPLIER_ADDON',      'mod_commande_fournisseur_muguet','chaine',0);
insert into llx_const (name, value, type, visible) values ('COMMANDE_SUPPLIER_ADDON_PDF',  'muscadet','chaine',0);
insert into llx_const (name, value, type, visible) values ('EXPEDITION_ADDON',    'enlevement','chaine',0);
insert into llx_const (name, value, type, visible) values ('EXPEDITION_ADDON_PDF','rouget','chaine',0);
insert into llx_const (name, value, type, visible) values ('FICHEINTER_ADDON',    'pacific','chaine',0);
insert into llx_const (name, value, type, visible) values ('FICHEINTER_ADDON_PDF','soleil','chaine',0);
insert into llx_const (name, value, type, visible) values ('FACTURE_ADDON',       'terre','chaine',0);
insert into llx_const (name, value, type, visible) values ('FACTURE_ADDON_PDF',   'crabe','chaine',0);


--
-- Forcer les locales
--
insert into llx_const (name, value, type, visible, note) VALUES ('MAIN_FORCE_SETLOCALE_LC_ALL',      'MAIN_FORCE_SETLOCALE_LC_ALL', 'chaine', 1, 'Pour forcer LC_ALL si pb de locale');
insert into llx_const (name, value, type, visible, note) VALUES ('MAIN_FORCE_SETLOCALE_LC_TIME',     'MAIN_FORCE_SETLOCALE_LC_TIME', 'chaine', 1, 'Pour forcer LC_TIME si pb de locale');
insert into llx_const (name, value, type, visible, note) VALUES ('MAIN_FORCE_SETLOCALE_LC_MONETARY', 'MAIN_FORCE_SETLOCALE_LC_MONETARY', 'chaine', 1, 'Pour forcer LC_MONETARY si pb de locale');
insert into llx_const (name, value, type, visible, note) VALUES ('MAIN_FORCE_SETLOCALE_LC_NUMERIC',  'MAIN_FORCE_SETLOCALE_LC_NUMERIC', 'chaine', 1, 'Mettre la valeur C si problème de centimes');

--
-- Duree de validite des propales
--
insert into llx_const (name, value, type, visible, note) VALUES ('PROPALE_VALIDITY_DURATION',      '15', 'chaine', 0, 'Durée de validitée des propales');


--
-- Barcode
--
insert into llx_const (name, value, type, note, visible) values ('GENBARCODE_LOCATION','/usr/local/bin/genbarcode','chaine','location of genbarcode',0);


--
-- Descriptif des plans comptables FR PCG99-ABREGE
--

delete from llx_accountingaccount;
delete from llx_accountingsystem;

insert into llx_accountingsystem (pcg_version, fk_pays, label, datec, fk_author, active) VALUES ('PCG99-ABREGE', 1, 'Plan de compte standard français abrégé', curdate(), null, 0);

insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  1,'PCG99-ABREGE','CAPIT', 'CAPITAL', '101', '1', 'Capital');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  2,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '105', '1', 'Ecarts de réévaluation');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  3,'PCG99-ABREGE','CAPIT', 'XXXXXX', '1061', '1', 'Réserve légale');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  4,'PCG99-ABREGE','CAPIT', 'XXXXXX', '1063', '1', 'Réserves statutaires ou contractuelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  5,'PCG99-ABREGE','CAPIT', 'XXXXXX', '1064', '1', 'Réserves réglementées');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  6,'PCG99-ABREGE','CAPIT', 'XXXXXX', '1068', '1', 'Autres réserves');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  7,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '108', '1', 'Compte de l''exploitant');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  8,'PCG99-ABREGE','CAPIT', 'XXXXXX',   '12', '1', 'Résultat de l''exercice');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  9,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '145', '1', 'Amortissements dérogatoires');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 10,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '146', '1', 'Provision spéciale de réévaluation');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 11,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '147', '1', 'Plus-values réinvesties');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 12,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '148', '1', 'Autres provisions réglementées');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 13,'PCG99-ABREGE','CAPIT', 'XXXXXX',   '15', '1', 'Provisions pour risques et charges');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 14,'PCG99-ABREGE','CAPIT', 'XXXXXX',   '16', '1', 'Emprunts et dettes assimilees');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 15,'PCG99-ABREGE','IMMO',  'XXXXXX',   '20', '2', 'Immobilisations incorporelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 16,'PCG99-ABREGE','IMMO',  'XXXXXX',  '201','20', 'Frais d''établissement');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 17,'PCG99-ABREGE','IMMO',  'XXXXXX',  '206','20', 'Droit au bail');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 18,'PCG99-ABREGE','IMMO',  'XXXXXX',  '207','20', 'Fonds commercial');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 19,'PCG99-ABREGE','IMMO',  'XXXXXX',  '208','20', 'Autres immobilisations incorporelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 20,'PCG99-ABREGE','IMMO',  'XXXXXX',   '21', '2', 'Immobilisations corporelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 21,'PCG99-ABREGE','IMMO',  'XXXXXX',   '23', '2', 'Immobilisations en cours');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 22,'PCG99-ABREGE','IMMO',  'XXXXXX',   '27', '2', 'Autres immobilisations financieres');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 23,'PCG99-ABREGE','IMMO',  'XXXXXX',  '280', '2', 'Amortissements des immobilisations incorporelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 24,'PCG99-ABREGE','IMMO',  'XXXXXX',  '281', '2', 'Amortissements des immobilisations corporelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 25,'PCG99-ABREGE','IMMO',  'XXXXXX',  '290', '2', 'Provisions pour dépréciation des immobilisations incorporelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 26,'PCG99-ABREGE','IMMO',  'XXXXXX',  '291', '2', 'Provisions pour dépréciation des immobilisations corporelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 27,'PCG99-ABREGE','IMMO',  'XXXXXX',  '297', '2', 'Provisions pour dépréciation des autres immobilisations financières');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 28,'PCG99-ABREGE','STOCK', 'XXXXXX',   '31', '3', 'Matieres premières');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 29,'PCG99-ABREGE','STOCK', 'XXXXXX',   '32', '3', 'Autres approvisionnements');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 30,'PCG99-ABREGE','STOCK', 'XXXXXX',   '33', '3', 'En-cours de production de biens');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 31,'PCG99-ABREGE','STOCK', 'XXXXXX',   '34', '3', 'En-cours de production de services');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 32,'PCG99-ABREGE','STOCK', 'XXXXXX',   '35', '3', 'Stocks de produits');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 33,'PCG99-ABREGE','STOCK', 'XXXXXX',   '37', '3', 'Stocks de marchandises');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 34,'PCG99-ABREGE','STOCK', 'XXXXXX',  '391', '3', 'Provisions pour dépréciation des matières premières');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 35,'PCG99-ABREGE','STOCK', 'XXXXXX',  '392', '3', 'Provisions pour dépréciation des autres approvisionnements');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 36,'PCG99-ABREGE','STOCK', 'XXXXXX',  '393', '3', 'Provisions pour dépréciation des en-cours de production de biens');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 37,'PCG99-ABREGE','STOCK', 'XXXXXX',  '394', '3', 'Provisions pour dépréciation des en-cours de production de services');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 38,'PCG99-ABREGE','STOCK', 'XXXXXX',  '395', '3', 'Provisions pour dépréciation des stocks de produits');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 39,'PCG99-ABREGE','STOCK', 'XXXXXX',  '397', '3', 'Provisions pour dépréciation des stocks de marchandises');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 40,'PCG99-ABREGE','TIERS', 'SUPPLIER','400', '4', 'Fournisseurs et Comptes rattachés');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 41,'PCG99-ABREGE','TIERS', 'XXXXXX',  '409', '4', 'Fournisseurs débiteurs');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 42,'PCG99-ABREGE','TIERS', 'CUSTOMER','410', '4', 'Clients et Comptes rattachés');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 43,'PCG99-ABREGE','TIERS', 'XXXXXX',  '419', '4', 'Clients créditeurs');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 44,'PCG99-ABREGE','TIERS', 'XXXXXX',  '421', '4', 'Personnel');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 45,'PCG99-ABREGE','TIERS', 'XXXXXX',  '428', '4', 'Personnel');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 46,'PCG99-ABREGE','TIERS', 'XXXXXX',   '43', '4', 'Sécurité sociale et autres organismes sociaux');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 47,'PCG99-ABREGE','TIERS', 'XXXXXX',  '444', '4', 'Etat - impôts sur bénéfice');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 48,'PCG99-ABREGE','TIERS', 'XXXXXX',  '445', '4', 'Etat - Taxes sur chiffre affaire');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 49,'PCG99-ABREGE','TIERS', 'XXXXXX',  '447', '4', 'Autres impôts, taxes et versements assimilés');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 50,'PCG99-ABREGE','TIERS', 'XXXXXX',   '45', '4', 'Groupe et associes');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 51,'PCG99-ABREGE','TIERS', 'XXXXXX',  '455','45', 'Associés');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 52,'PCG99-ABREGE','TIERS', 'XXXXXX',   '46', '4', 'Débiteurs divers et créditeurs divers');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 53,'PCG99-ABREGE','TIERS', 'XXXXXX',   '47', '4', 'Comptes transitoires ou d''attente');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 54,'PCG99-ABREGE','TIERS', 'XXXXXX',  '481', '4', 'Charges à répartir sur plusieurs exercices');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 55,'PCG99-ABREGE','TIERS', 'XXXXXX',  '486', '4', 'Charges constatées d''avance');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 56,'PCG99-ABREGE','TIERS', 'XXXXXX',  '487', '4', 'Produits constatés d''avance');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 57,'PCG99-ABREGE','TIERS', 'XXXXXX',  '491', '4', 'Provisions pour dépréciation des comptes de clients');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 58,'PCG99-ABREGE','TIERS', 'XXXXXX',  '496', '4', 'Provisions pour dépréciation des comptes de débiteurs divers');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 59,'PCG99-ABREGE','FINAN', 'XXXXXX',   '50', '5', 'Valeurs mobilières de placement');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 60,'PCG99-ABREGE','FINAN', 'BANK',     '51', '5', 'Banques, établissements financiers et assimilés');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 61,'PCG99-ABREGE','FINAN', 'CASH',     '53', '5', 'Caisse');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 62,'PCG99-ABREGE','FINAN', 'XXXXXX',   '54', '5', 'Régies d''avance et accréditifs');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 63,'PCG99-ABREGE','FINAN', 'XXXXXX',   '58', '5', 'Virements internes');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 64,'PCG99-ABREGE','FINAN', 'XXXXXX',  '590', '5', 'Provisions pour dépréciation des valeurs mobilières de placement');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 65,'PCG99-ABREGE','CHARGE','PRODUCT',  '60', '6', 'Achats');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 66,'PCG99-ABREGE','CHARGE','XXXXXX',  '603','60', 'Variations des stocks');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 67,'PCG99-ABREGE','CHARGE','SERVICE',  '61', '6', 'Services extérieurs');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 68,'PCG99-ABREGE','CHARGE','XXXXXX',   '62', '6', 'Autres services extérieurs');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 69,'PCG99-ABREGE','CHARGE','XXXXXX',   '63', '6', 'Impôts, taxes et versements assimiles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 70,'PCG99-ABREGE','CHARGE','XXXXXX',  '641', '6', 'Rémunérations du personnel');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 71,'PCG99-ABREGE','CHARGE','XXXXXX',  '644', '6', 'Rémunération du travail de l''exploitant');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 72,'PCG99-ABREGE','CHARGE','SOCIAL',  '645', '6', 'Charges de sécurité sociale et de prévoyance');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 73,'PCG99-ABREGE','CHARGE','XXXXXX',  '646', '6', 'Cotisations sociales personnelles de l''exploitant');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 74,'PCG99-ABREGE','CHARGE','XXXXXX',   '65', '6', 'Autres charges de gestion courante');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 75,'PCG99-ABREGE','CHARGE','XXXXXX',   '66', '6', 'Charges financières');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 76,'PCG99-ABREGE','CHARGE','XXXXXX',   '67', '6', 'Charges exceptionnelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 77,'PCG99-ABREGE','CHARGE','XXXXXX',  '681', '6', 'Dotations aux amortissements et aux provisions');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 78,'PCG99-ABREGE','CHARGE','XXXXXX',  '686', '6', 'Dotations aux amortissements et aux provisions');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 79,'PCG99-ABREGE','CHARGE','XXXXXX',  '687', '6', 'Dotations aux amortissements et aux provisions');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 80,'PCG99-ABREGE','CHARGE','XXXXXX',  '691', '6', 'Participation des salariés aux résultats');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 81,'PCG99-ABREGE','CHARGE','XXXXXX',  '695', '6', 'Impôts sur les bénéfices');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 82,'PCG99-ABREGE','CHARGE','XXXXXX',  '697', '6', 'Imposition forfaitaire annuelle des sociétés');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 83,'PCG99-ABREGE','CHARGE','XXXXXX',  '699', '6', 'Produits');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 84,'PCG99-ABREGE','PROD',  'PRODUCT', '701', '7', 'Ventes de produits finis');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 85,'PCG99-ABREGE','PROD',  'SERVICE', '706', '7', 'Prestations de services');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 86,'PCG99-ABREGE','PROD',  'PRODUCT', '707', '7', 'Ventes de marchandises');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 87,'PCG99-ABREGE','PROD',  'PRODUCT', '708', '7', 'Produits des activités annexes');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 88,'PCG99-ABREGE','PROD',  'XXXXXX',  '709', '7', 'Rabais, remises et ristournes accordés par l''entreprise');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 89,'PCG99-ABREGE','PROD',  'XXXXXX',  '713', '7', 'Variation des stocks');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 90,'PCG99-ABREGE','PROD',  'XXXXXX',   '72', '7', 'Production immobilisée');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 91,'PCG99-ABREGE','PROD',  'XXXXXX',   '73', '7', 'Produits nets partiels sur opérations à long terme');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 92,'PCG99-ABREGE','PROD',  'XXXXXX',   '74', '7', 'Subventions d''exploitation');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 93,'PCG99-ABREGE','PROD',  'XXXXXX',   '75', '7', 'Autres produits de gestion courante');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 94,'PCG99-ABREGE','PROD',  'XXXXXX',  '753','75', 'Jetons de présence et rémunérations d''administrateurs, gérants,...');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 95,'PCG99-ABREGE','PROD',  'XXXXXX',  '754','75', 'Ristournes perçues des coopératives');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 96,'PCG99-ABREGE','PROD',  'XXXXXX',  '755','75', 'Quotes-parts de résultat sur opérations faites en commun');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 97,'PCG99-ABREGE','PROD',  'XXXXXX',   '76', '7', 'Produits financiers');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 98,'PCG99-ABREGE','PROD',  'XXXXXX',   '77', '7', 'Produits exceptionnels');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 99,'PCG99-ABREGE','PROD',  'XXXXXX',  '781', '7', 'Reprises sur amortissements et provisions');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (100,'PCG99-ABREGE','PROD',  'XXXXXX',  '786', '7', 'Reprises sur provisions pour risques');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (101,'PCG99-ABREGE','PROD',  'XXXXXX',  '787', '7', 'Reprises sur provisions');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (102,'PCG99-ABREGE','PROD',  'XXXXXX',   '79', '7', 'Transferts de charges');



-- Dictionnaires llx_c

--
-- Types action comm
--

delete from llx_c_actioncomm where id in (1,2,3,4,5,8,9,50);
insert into llx_c_actioncomm (id, code, type, libelle, module) values ( 1, 'AC_TEL',  'system', 'Appel Téléphonique' ,NULL);
insert into llx_c_actioncomm (id, code, type, libelle, module) values ( 2, 'AC_FAX',  'system', 'Envoi Fax'          ,NULL);
insert into llx_c_actioncomm (id, code, type, libelle, module) values ( 3, 'AC_PROP', 'system', 'Envoi Proposition'  ,'propal');
insert into llx_c_actioncomm (id, code, type, libelle, module) values ( 4, 'AC_EMAIL','system', 'Envoi Email'        ,NULL);
insert into llx_c_actioncomm (id, code, type, libelle, module) values ( 5, 'AC_RDV',  'system', 'Rendez-vous'        ,NULL);
insert into llx_c_actioncomm (id, code, type, libelle, module) values ( 8, 'AC_COM',  'system', 'Envoi Commande'     ,'order');
insert into llx_c_actioncomm (id, code, type, libelle, module) values ( 9, 'AC_FAC',  'system', 'Envoi Facture'      ,'invoice');
insert into llx_c_actioncomm (id, code, type, libelle, module) values (50, 'AC_OTH',  'system', 'Autre'              ,NULL);

--
-- Ape
--
delete from llx_c_ape;


--
-- Types de charges
--

insert into llx_c_chargesociales (id, libelle, deductible, active, actioncompta) values ( 1, 'Allocations familiales', 1,1,'TAXFAM');
insert into llx_c_chargesociales (id, libelle, deductible, active, actioncompta) values ( 2, 'GSG Deductible',         1,1,'TAXCSGD');
insert into llx_c_chargesociales (id, libelle, deductible, active, actioncompta) values ( 3, 'GSG/CRDS NON Deductible',0,1,'TAXCSGND');
insert into llx_c_chargesociales (id, libelle, deductible, active, actioncompta) values (10, 'Taxe apprenttissage',    0,1,'TAXAPP');
insert into llx_c_chargesociales (id, libelle, deductible, active, actioncompta) values (11, 'Taxe professionnelle',   0,1,'TAXPRO');
insert into llx_c_chargesociales (id, libelle, deductible, active, actioncompta) values (20, 'Impots locaux/fonciers', 0,1,'TAXFON');
insert into llx_c_chargesociales (id, libelle, deductible, active, actioncompta) values (30, 'Assurance Sante (SECU-URSSAF)',  0,1,'TAXSECU');
insert into llx_c_chargesociales (id, libelle, deductible, active, actioncompta) values (40, 'Mutuelle',                       0,1,'TAXMUT');
insert into llx_c_chargesociales (id, libelle, deductible, active, actioncompta) values (50, 'Assurance vieillesse (CNAV)',    0,1,'TAXRET');
insert into llx_c_chargesociales (id, libelle, deductible, active, actioncompta) values (60, 'Assurance Chomage (ASSEDIC)',    0,1,'TAXCHOM');

--
-- Civilites
--

delete from llx_c_civilite;
insert into llx_c_civilite (rowid, code, civilite, active) values (1 , 'MME',  'Madame', 1);
insert into llx_c_civilite (rowid, code, civilite, active) values (3 , 'MR',   'Monsieur', 1);
insert into llx_c_civilite (rowid, code, civilite, active) values (5 , 'MLE',  'Mademoiselle', 1);
insert into llx_c_civilite (rowid, code, civilite, active) values (7 , 'MTRE', 'Maître', 1);


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
-- Pays
--

-- delete from llx_c_pays;
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
insert into llx_c_pays (rowid,code,libelle) values (22, 'SN', 'Sénégal'        );
insert into llx_c_pays (rowid,code,libelle) values (23, 'AR', 'Argentine'      );
insert into llx_c_pays (rowid,code,libelle) values (24, 'CM', 'Cameroun'       );
insert into llx_c_pays (rowid,code,libelle) values (25, 'PT', 'Portugal'       );
insert into llx_c_pays (rowid,code,libelle) values (26, 'SA', 'Arabie Saoudite');
insert into llx_c_pays (rowid,code,libelle) values (27, 'MC', 'Monaco'         );
insert into llx_c_pays (rowid,code,libelle) values (28, 'AU', 'Australie'      );
insert into llx_c_pays (rowid,code,libelle) values (29, 'SG', 'Singapoure'     );
insert into llx_c_pays (rowid,code,libelle) values (30, 'AF', 'Afghanistan'    );
insert into llx_c_pays (rowid,code,libelle) values (31, 'AX', 'Iles Aland'     );
insert into llx_c_pays (rowid,code,libelle) values (32, 'AL', 'Albanie'        );
insert into llx_c_pays (rowid,code,libelle) values (33, 'AS', 'Samoa américaines');
insert into llx_c_pays (rowid,code,libelle) values (34, 'AD', 'Andorre'        );
insert into llx_c_pays (rowid,code,libelle) values (35, 'AO', 'Angola'         );
insert into llx_c_pays (rowid,code,libelle) values (36, 'AI', 'Anguilla'       );
insert into llx_c_pays (rowid,code,libelle) values (37, 'AQ', 'Antarctique'    );
insert into llx_c_pays (rowid,code,libelle) values (38, 'AG', 'Antigua-et-Barbuda');
insert into llx_c_pays (rowid,code,libelle) values (39, 'AM', 'Arménie'        );
insert into llx_c_pays (rowid,code,libelle) values (40, 'AW', 'Aruba'          );
insert into llx_c_pays (rowid,code,libelle) values (41, 'AT', 'Autriche'       );
insert into llx_c_pays (rowid,code,libelle) values (42, 'AZ', 'Azerbaïdjan'    );
insert into llx_c_pays (rowid,code,libelle) values (43, 'BS', 'Bahamas'        );
insert into llx_c_pays (rowid,code,libelle) values (44, 'BH', 'Bahreïn'        );
insert into llx_c_pays (rowid,code,libelle) values (45, 'BD', 'Bangladesh'     );
insert into llx_c_pays (rowid,code,libelle) values (46, 'BB', 'Barbade'        );
insert into llx_c_pays (rowid,code,libelle) values (47, 'BY', 'Biélorussie'    );
insert into llx_c_pays (rowid,code,libelle) values (48, 'BZ', 'Belize'         );
insert into llx_c_pays (rowid,code,libelle) values (49, 'BJ', 'Bénin'          );
insert into llx_c_pays (rowid,code,libelle) values (50, 'BM', 'Bermudes'       );
insert into llx_c_pays (rowid,code,libelle) values (51, 'BT', 'Bhoutan'        );
insert into llx_c_pays (rowid,code,libelle) values (52, 'BO', 'Bolivie'        );
insert into llx_c_pays (rowid,code,libelle) values (53, 'BA', 'Bosnie-Herzégovine');
insert into llx_c_pays (rowid,code,libelle) values (54, 'BW', 'Botswana'       );
insert into llx_c_pays (rowid,code,libelle) values (55, 'BV', 'Ile Bouvet'     );
insert into llx_c_pays (rowid,code,libelle) values (56, 'BR', 'Brésil'         );
insert into llx_c_pays (rowid,code,libelle) values (57, 'IO', 'Territoire britannique de l\'Océan Indien');
insert into llx_c_pays (rowid,code,libelle) values (58, 'BN', 'Brunei'         );
insert into llx_c_pays (rowid,code,libelle) values (59, 'BG', 'Bulgarie'       );
insert into llx_c_pays (rowid,code,libelle) values (60, 'BF', 'Burkina Faso'   );
insert into llx_c_pays (rowid,code,libelle) values (61, 'BI', 'Burundi'        );
insert into llx_c_pays (rowid,code,libelle) values (62, 'KH', 'Cambodge'       );
insert into llx_c_pays (rowid,code,libelle) values (63, 'CV', 'Cap-Vert'       );
insert into llx_c_pays (rowid,code,libelle) values (64, 'KY', 'Iles Cayman'    );
insert into llx_c_pays (rowid,code,libelle) values (65, 'CF', 'République centrafricaine');
insert into llx_c_pays (rowid,code,libelle) values (66, 'TD', 'Tchad'          );
insert into llx_c_pays (rowid,code,libelle) values (67, 'CL', 'Chili'          );
insert into llx_c_pays (rowid,code,libelle) values (68, 'CX', 'Ile Christmas'  );
insert into llx_c_pays (rowid,code,libelle) values (69, 'CC', 'Iles des Cocos (Keeling)');
insert into llx_c_pays (rowid,code,libelle) values (70, 'CO', 'Colombie'       );
insert into llx_c_pays (rowid,code,libelle) values (71, 'KM', 'Comores'        );
insert into llx_c_pays (rowid,code,libelle) values (72, 'CG', 'Congo'          );
insert into llx_c_pays (rowid,code,libelle) values (73, 'CD', 'République démocratique du Congo');
insert into llx_c_pays (rowid,code,libelle) values (74, 'CK', 'Iles Cook'      );
insert into llx_c_pays (rowid,code,libelle) values (75, 'CR', 'Costa Rica'     );
insert into llx_c_pays (rowid,code,libelle) values (76, 'HR', 'Croatie'        );
insert into llx_c_pays (rowid,code,libelle) values (77, 'CU', 'Cuba'           );
insert into llx_c_pays (rowid,code,libelle) values (78, 'CY', 'Chypre'         );
insert into llx_c_pays (rowid,code,libelle) values (79, 'CZ', 'République Tchèque');
insert into llx_c_pays (rowid,code,libelle) values (80, 'DK', 'Danemark'       );
insert into llx_c_pays (rowid,code,libelle) values (81, 'DJ', 'Djibouti'       );
insert into llx_c_pays (rowid,code,libelle) values (82, 'DM', 'Dominique'      );
insert into llx_c_pays (rowid,code,libelle) values (83, 'DO', 'République Dominicaine');
insert into llx_c_pays (rowid,code,libelle) values (84, 'EC', 'Equateur'       );
insert into llx_c_pays (rowid,code,libelle) values (85, 'EG', 'Egypte'         );
insert into llx_c_pays (rowid,code,libelle) values (86, 'SV', 'Salvador'       );
insert into llx_c_pays (rowid,code,libelle) values (87, 'GQ', 'Guinée Equatoriale');
insert into llx_c_pays (rowid,code,libelle) values (88, 'ER', 'Erythrée'       );
insert into llx_c_pays (rowid,code,libelle) values (89, 'EE', 'Estonie'        );
insert into llx_c_pays (rowid,code,libelle) values (90, 'ET', 'Ethiopie'       );
insert into llx_c_pays (rowid,code,libelle) values (91, 'FK', 'Iles Falkland'  );
insert into llx_c_pays (rowid,code,libelle) values (92, 'FO', 'Iles Féroé'     );
insert into llx_c_pays (rowid,code,libelle) values (93, 'FJ', 'Iles Fidji'     );
insert into llx_c_pays (rowid,code,libelle) values (94, 'FI', 'Finlande'       );
insert into llx_c_pays (rowid,code,libelle) values (95, 'GF', 'Guyane française');
insert into llx_c_pays (rowid,code,libelle) values (96, 'PF', 'Polynésie française');
insert into llx_c_pays (rowid,code,libelle) values (97, 'TF', 'Terres australes françaises');
insert into llx_c_pays (rowid,code,libelle) values (98, 'GM', 'Gambie'         );
insert into llx_c_pays (rowid,code,libelle) values (99, 'GE', 'Géorgie'       );
insert into llx_c_pays (rowid,code,libelle) values (100, 'GH', 'Ghana'         );
insert into llx_c_pays (rowid,code,libelle) values (101, 'GI', 'Gibraltar'     );
insert into llx_c_pays (rowid,code,libelle) values (102, 'GR', 'Grèce'         );
insert into llx_c_pays (rowid,code,libelle) values (103, 'GL', 'Groenland'     );
insert into llx_c_pays (rowid,code,libelle) values (104, 'GD', 'Grenade'       );
insert into llx_c_pays (rowid,code,libelle) values (105, 'GP', 'Guadeloupe'    );
insert into llx_c_pays (rowid,code,libelle) values (106, 'GU', 'Guam'          );
insert into llx_c_pays (rowid,code,libelle) values (107, 'GT', 'Guatemala'     );
insert into llx_c_pays (rowid,code,libelle) values (108, 'GN', 'Guinée'        );
insert into llx_c_pays (rowid,code,libelle) values (109, 'GW', 'Guinée-Bissao' );
insert into llx_c_pays (rowid,code,libelle) values (110, 'GY', 'Guyana'        );
insert into llx_c_pays (rowid,code,libelle) values (111, 'HT', 'Haïti'         );
insert into llx_c_pays (rowid,code,libelle) values (112, 'HM', 'Iles Heard et McDonald');
insert into llx_c_pays (rowid,code,libelle) values (113, 'VA', 'Saint-Siège (Vatican)');
insert into llx_c_pays (rowid,code,libelle) values (114, 'HN', 'Honduras'      );
insert into llx_c_pays (rowid,code,libelle) values (115, 'HK', 'Hong Kong'     );
insert into llx_c_pays (rowid,code,libelle) values (116, 'IS', 'Islande'       );
insert into llx_c_pays (rowid,code,libelle) values (117, 'IN', 'Inde'          );
insert into llx_c_pays (rowid,code,libelle) values (118, 'ID', 'Indonésie'     );
insert into llx_c_pays (rowid,code,libelle) values (119, 'IR', 'Iran'          );
insert into llx_c_pays (rowid,code,libelle) values (120, 'IQ', 'Iraq'          );
insert into llx_c_pays (rowid,code,libelle) values (121, 'IL', 'Israël'        );
insert into llx_c_pays (rowid,code,libelle) values (122, 'JM', 'Jamaïque'      );
insert into llx_c_pays (rowid,code,libelle) values (123, 'JP', 'Japon'         );
insert into llx_c_pays (rowid,code,libelle) values (124, 'JO', 'Jordanie'      );
insert into llx_c_pays (rowid,code,libelle) values (125, 'KZ', 'Kazakhstan'    );
insert into llx_c_pays (rowid,code,libelle) values (126, 'KE', 'Kenya'         );
insert into llx_c_pays (rowid,code,libelle) values (127, 'KI', 'Kiribati'      );
insert into llx_c_pays (rowid,code,libelle) values (128, 'KP', 'Corée du Nord' );
insert into llx_c_pays (rowid,code,libelle) values (129, 'KR', 'Corée du Sud'  );
insert into llx_c_pays (rowid,code,libelle) values (130, 'KW', 'Koweït'        );
insert into llx_c_pays (rowid,code,libelle) values (131, 'KG', 'Kirghizistan'  );
insert into llx_c_pays (rowid,code,libelle) values (132, 'LA', 'Laos'          );
insert into llx_c_pays (rowid,code,libelle) values (133, 'LV', 'Lettonie'      );
insert into llx_c_pays (rowid,code,libelle) values (134, 'LB', 'Liban'         );
insert into llx_c_pays (rowid,code,libelle) values (135, 'LS', 'Lesotho'       );
insert into llx_c_pays (rowid,code,libelle) values (136, 'LR', 'Liberia'       );
insert into llx_c_pays (rowid,code,libelle) values (137, 'LY', 'Libye'         );
insert into llx_c_pays (rowid,code,libelle) values (138, 'LI', 'Liechtenstein' );
insert into llx_c_pays (rowid,code,libelle) values (139, 'LT', 'Lituanie'      );
insert into llx_c_pays (rowid,code,libelle) values (140, 'LU', 'Luxembourg'    );
insert into llx_c_pays (rowid,code,libelle) values (141, 'MO', 'Macao'         );
insert into llx_c_pays (rowid,code,libelle) values (142, 'MK', 'ex-République yougoslave de Macédoine');
insert into llx_c_pays (rowid,code,libelle) values (143, 'MG', 'Madagascar'    );
insert into llx_c_pays (rowid,code,libelle) values (144, 'MW', 'Malawi'        );
insert into llx_c_pays (rowid,code,libelle) values (145, 'MY', 'Malaisie'      );
insert into llx_c_pays (rowid,code,libelle) values (146, 'MV', 'Maldives'      );
insert into llx_c_pays (rowid,code,libelle) values (147, 'ML', 'Mali'          );
insert into llx_c_pays (rowid,code,libelle) values (148, 'MT', 'Malte'         );
insert into llx_c_pays (rowid,code,libelle) values (149, 'MH', 'Iles Marshall' );
insert into llx_c_pays (rowid,code,libelle) values (150, 'MQ', 'Martinique'    );
insert into llx_c_pays (rowid,code,libelle) values (151, 'MR', 'Mauritanie'    );
insert into llx_c_pays (rowid,code,libelle) values (152, 'MU', 'Maurice'       );
insert into llx_c_pays (rowid,code,libelle) values (153, 'YT', 'Mayotte'       );
insert into llx_c_pays (rowid,code,libelle) values (154, 'MX', 'Mexique'       );
insert into llx_c_pays (rowid,code,libelle) values (155, 'FM', 'Micronésie'    );
insert into llx_c_pays (rowid,code,libelle) values (156, 'MD', 'Moldavie'      );
insert into llx_c_pays (rowid,code,libelle) values (157, 'MN', 'Mongolie'      );
insert into llx_c_pays (rowid,code,libelle) values (158, 'MS', 'Monserrat'     );
insert into llx_c_pays (rowid,code,libelle) values (159, 'MZ', 'Mozambique'    );
insert into llx_c_pays (rowid,code,libelle) values (160, 'MM', 'Birmanie (Myanmar)'      );
insert into llx_c_pays (rowid,code,libelle) values (161, 'NA', 'Namibie'       );
insert into llx_c_pays (rowid,code,libelle) values (162, 'NR', 'Nauru'         );
insert into llx_c_pays (rowid,code,libelle) values (163, 'NP', 'Népal'         );
insert into llx_c_pays (rowid,code,libelle) values (164, 'AN', 'Antilles néerlandaises');
insert into llx_c_pays (rowid,code,libelle) values (165, 'NC', 'Nouvelle-Calédonie');
insert into llx_c_pays (rowid,code,libelle) values (166, 'NZ', 'Nouvelle-Zélande');
insert into llx_c_pays (rowid,code,libelle) values (167, 'NI', 'Nicaragua'     );
insert into llx_c_pays (rowid,code,libelle) values (168, 'NE', 'Niger'         );
insert into llx_c_pays (rowid,code,libelle) values (169, 'NG', 'Nigeria'       );
insert into llx_c_pays (rowid,code,libelle) values (170, 'NU', 'Nioué'         );
insert into llx_c_pays (rowid,code,libelle) values (171, 'NF', 'Ile Norfolk'   );
insert into llx_c_pays (rowid,code,libelle) values (172, 'MP', 'Mariannes du Nord');
insert into llx_c_pays (rowid,code,libelle) values (173, 'NO', 'Norvège'       );
insert into llx_c_pays (rowid,code,libelle) values (174, 'OM', 'Oman'          );
insert into llx_c_pays (rowid,code,libelle) values (175, 'PK', 'Pakistan'      );
insert into llx_c_pays (rowid,code,libelle) values (176, 'PW', 'Palaos'         );
insert into llx_c_pays (rowid,code,libelle) values (177, 'PS', 'territoire Palestinien Occupé');
insert into llx_c_pays (rowid,code,libelle) values (178, 'PA', 'Panama'        );
insert into llx_c_pays (rowid,code,libelle) values (179, 'PG', 'Papouasie-Nouvelle-Guinée');
insert into llx_c_pays (rowid,code,libelle) values (180, 'PY', 'Paraguay'      );
insert into llx_c_pays (rowid,code,libelle) values (181, 'PE', 'Pérou'         );
insert into llx_c_pays (rowid,code,libelle) values (182, 'PH', 'Philippines'   );
insert into llx_c_pays (rowid,code,libelle) values (183, 'PN', 'Iles Pitcairn' );
insert into llx_c_pays (rowid,code,libelle) values (184, 'PL', 'Pologne'       );
insert into llx_c_pays (rowid,code,libelle) values (185, 'PR', 'Porto Rico'    );
insert into llx_c_pays (rowid,code,libelle) values (186, 'QA', 'Qatar'         );
insert into llx_c_pays (rowid,code,libelle) values (187, 'RE', 'Réunion'       );
insert into llx_c_pays (rowid,code,libelle) values (188, 'RO', 'Roumanie'      );
insert into llx_c_pays (rowid,code,libelle) values (189, 'RW', 'Rwanda'        );
insert into llx_c_pays (rowid,code,libelle) values (190, 'SH', 'Sainte-Hélène' );
insert into llx_c_pays (rowid,code,libelle) values (191, 'KN', 'Saint-Christophe-et-Niévès');
insert into llx_c_pays (rowid,code,libelle) values (192, 'LC', 'Sainte-Lucie'  );
insert into llx_c_pays (rowid,code,libelle) values (193, 'PM', 'Saint-Pierre-et-Miquelon');
insert into llx_c_pays (rowid,code,libelle) values (194, 'VC', 'Saint-Vincent-et-les-Grenadines');
insert into llx_c_pays (rowid,code,libelle) values (195, 'WS', 'Samoa'         );
insert into llx_c_pays (rowid,code,libelle) values (196, 'SM', 'Saint-Marin'   );
insert into llx_c_pays (rowid,code,libelle) values (197, 'ST', 'Sao Tomé-et-Principe');
insert into llx_c_pays (rowid,code,libelle) values (198, 'RS', 'Serbie'        );
insert into llx_c_pays (rowid,code,libelle) values (199, 'SC', 'Seychelles'    );
insert into llx_c_pays (rowid,code,libelle) values (200, 'SL', 'Sierra Leone'  );
insert into llx_c_pays (rowid,code,libelle) values (201, 'SK', 'Slovaquie'     );
insert into llx_c_pays (rowid,code,libelle) values (202, 'SI', 'Slovénie'      );
insert into llx_c_pays (rowid,code,libelle) values (203, 'SB', 'Iles Salomon'  );
insert into llx_c_pays (rowid,code,libelle) values (204, 'SO', 'Somalie'       );
insert into llx_c_pays (rowid,code,libelle) values (205, 'ZA', 'Afrique du Sud');
insert into llx_c_pays (rowid,code,libelle) values (206, 'GS', 'Iles Géorgie du Sud et Sandwich du Sud');
insert into llx_c_pays (rowid,code,libelle) values (207, 'LK', 'Sri Lanka'     );
insert into llx_c_pays (rowid,code,libelle) values (208, 'SD', 'Soudan'        );
insert into llx_c_pays (rowid,code,libelle) values (209, 'SR', 'Suriname'      );
insert into llx_c_pays (rowid,code,libelle) values (210, 'SJ', 'Iles Svalbard et Jan Mayen');
insert into llx_c_pays (rowid,code,libelle) values (211, 'SZ', 'Swaziland'     );
insert into llx_c_pays (rowid,code,libelle) values (212, 'SY', 'Syrie'         );
insert into llx_c_pays (rowid,code,libelle) values (213, 'TW', 'Taïwan'        );
insert into llx_c_pays (rowid,code,libelle) values (214, 'TJ', 'Tadjikistan'   );
insert into llx_c_pays (rowid,code,libelle) values (215, 'TZ', 'Tanzanie'      );
insert into llx_c_pays (rowid,code,libelle) values (216, 'TH', 'Thaïlande'     );
insert into llx_c_pays (rowid,code,libelle) values (217, 'TL', 'Timor Oriental');
insert into llx_c_pays (rowid,code,libelle) values (218, 'TK', 'Tokélaou'      );
insert into llx_c_pays (rowid,code,libelle) values (219, 'TO', 'Tonga'         );
insert into llx_c_pays (rowid,code,libelle) values (220, 'TT', 'Trinité-et-Tobago');
insert into llx_c_pays (rowid,code,libelle) values (221, 'TR', 'Turquie'       );
insert into llx_c_pays (rowid,code,libelle) values (222, 'TM', 'Turkménistan'  );
insert into llx_c_pays (rowid,code,libelle) values (223, 'TC', 'Iles Turks-et-Caicos');
insert into llx_c_pays (rowid,code,libelle) values (224, 'TV', 'Tuvalu'        );
insert into llx_c_pays (rowid,code,libelle) values (225, 'UG', 'Ouganda'       );
insert into llx_c_pays (rowid,code,libelle) values (226, 'UA', 'Ukraine'       );
insert into llx_c_pays (rowid,code,libelle) values (227, 'AE', 'Émirats arabes unis');
insert into llx_c_pays (rowid,code,libelle) values (228, 'UM', 'Iles mineures éloignées des États-Unis');
insert into llx_c_pays (rowid,code,libelle) values (229, 'UY', 'Uruguay'       );
insert into llx_c_pays (rowid,code,libelle) values (230, 'UZ', 'Ouzbékistan'   );
insert into llx_c_pays (rowid,code,libelle) values (231, 'VU', 'Vanuatu'       );
insert into llx_c_pays (rowid,code,libelle) values (232, 'VE', 'Vénézuela'     );
insert into llx_c_pays (rowid,code,libelle) values (233, 'VN', 'Viêt Nam'      );
insert into llx_c_pays (rowid,code,libelle) values (234, 'VG', 'Iles Vierges britanniques');
insert into llx_c_pays (rowid,code,libelle) values (235, 'VI', 'Iles Vierges américaines');
insert into llx_c_pays (rowid,code,libelle) values (236, 'WF', 'Wallis-et-Futuna');
insert into llx_c_pays (rowid,code,libelle) values (237, 'EH', 'Sahara occidental');
insert into llx_c_pays (rowid,code,libelle) values (238, 'YE', 'Yémen'         );
insert into llx_c_pays (rowid,code,libelle) values (239, 'ZM', 'Zambie'        );
insert into llx_c_pays (rowid,code,libelle) values (240, 'ZW', 'Zimbabwe'      );
insert into llx_c_pays (rowid,code,libelle) values (241, 'GG', 'Guernesey'     );
insert into llx_c_pays (rowid,code,libelle) values (242, 'IM', 'Ile de Man'    );
insert into llx_c_pays (rowid,code,libelle) values (243, 'JE', 'Jersey'        );
insert into llx_c_pays (rowid,code,libelle) values (244, 'ME', 'Monténégro'    );
insert into llx_c_pays (rowid,code,libelle) values (245, 'BL', 'Saint-Barthélemy');
insert into llx_c_pays (rowid,code,libelle) values (246, 'MF', 'Saint-Martin'  );


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
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'54','Société à responsabilité limitée (SARL)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'55','Société anonyme à conseil d\'administration');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'56','Société anonyme à directoire');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'57','Société par actions simplifiée');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'58','Entreprise Unipersonnelle à Responsabilité Limitée (EURL)');
                                                                     
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'61','Caisse d\'épargne et de prévoyance');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'62','Groupement d\'intérêt économique');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'63','Société coopérative agricole');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'64','Société non commerciale d\'assurances');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'65','Société civile');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'69','Autres personnes de droit privé inscrites au RCS');
                                                                     
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'71','Administration de l\'état');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'72','Collectivité territoriale');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'73','Établissement public administratif');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'74','Autre personne morale de droit public administratif');
                                                                     
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'81','Organisme gérant régime de protection social à adhésion obligatoire');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'82','Organisme mutualiste');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'83','Comité d\'entreprise');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'84','Organisme professionnel');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'85','Organisme de retraite à adhésion non obligatoire');
                                                                     
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'91','Syndicat de propriétaires');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'92','Association loi 1901 ou assimilé');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'93','Fondation');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'99','Autre personne morale de droit privé');

-- Pour la Belgique
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

-- Pour la Suisse
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '600', 'Raison Individuelle');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '601', 'Société Simple');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '602', 'Société en nom collectif');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '603', 'Société en commandite');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '604', 'Société anonyme (SA)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '605', 'Société en commandite par actions');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '606', 'Société à responsabilité limitée (SARL)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '607', 'Société coopérative');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '608', 'Association');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '609', 'Fondation');

-- Pour le Royaume Uni
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (7, '700', 'Sole Trader');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (7, '701', 'Partnership');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (7, '702', 'Private Limited Company by shares - (LTD)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (7, '703', 'Public Limited Company');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (7, '704', 'Workers Cooperative');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (7, '705', 'Limited Liability Partnership');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (7, '706', 'Franchise');

-- Pour la Tunisie (Formes les plus utilisées)
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (10, '1000','Société à responsabilité limitée SARL');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (10, '1001','Société en Nom Collectif');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (10, '1002','Société en Commandite Simple');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (10, '1003','société en participation');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (10, '1004','Société Anonyme SA');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (10, '1005','Société Unipersonnelle à Responsabilité Limitée SUARL');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (10, '1006','Groupement d\'intérêt économique GEI');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (10, '1007','Groupe de sociétés');

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
insert into llx_c_paiement (id,code,libelle,type,active) values (8, 'TRA', 'Traite',            2,0);
insert into llx_c_paiement (id,code,libelle,type,active) values (9, 'LCR', 'LCR',               2,0);
insert into llx_c_paiement (id,code,libelle,type,active) values (10,'FAC', 'Factor',            2,0);
insert into llx_c_paiement (id,code,libelle,type,active) values (11,'PRO', 'Proforma',          2,0);


--
-- Regions
--

insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (0,0,0,'0',0,'-');
-- Regions de France (id pays=1)
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 101, 1,   1,'97105',3,'Guadeloupe');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 102, 1,   2,'97209',3,'Martinique');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 103, 1,   3,'97302',3,'Guyane');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 104, 1,   4,'97411',3,'Réunion');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 105, 1,  11,'75056',1,'Île-de-France');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 106, 1,  21,'51108',0,'Champagne-Ardenne');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 107, 1,  22,'80021',0,'Picardie');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 108, 1,  23,'76540',0,'Haute-Normandie');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 109, 1,  24,'45234',2,'Centre');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 110, 1,  25,'14118',0,'Basse-Normandie');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 111, 1,  26,'21231',0,'Bourgogne');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 112, 1,  31,'59350',2,'Nord-Pas-de-Calais');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 113, 1,  41,'57463',0,'Lorraine');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 114, 1,  42,'67482',1,'Alsace');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 115, 1,  43,'25056',0,'Franche-Comté');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 116, 1,  52,'44109',4,'Pays de la Loire');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 117, 1,  53,'35238',0,'Bretagne');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 118, 1,  54,'86194',2,'Poitou-Charentes');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 119, 1,  72,'33063',1,'Aquitaine');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 120, 1,  73,'31555',0,'Midi-Pyrénées');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 121, 1,  74,'87085',2,'Limousin');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 122, 1,  82,'69123',2,'Rhône-Alpes');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 123, 1,  83,'63113',1,'Auvergne');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 124, 1,  91,'34172',2,'Languedoc-Roussillon');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 125, 1,  93,'13055',0,'Provence-Alpes-Côte d\'Azur');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 126, 1,  94,'2A004',0,'Corse');

-- Regions de Belgique (id pays=2)
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 201, 2, 201,     '',1,'Flandre');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 202, 2, 202,     '',2,'Wallonie');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 203, 2, 203,     '',3,'Bruxelles-Capitale');

-- Regions de Tunisie (id pays=10)
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1001,10,1001, '',0,'Ariana');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1002,10,1002, '',0,'Béja');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1003,10,1003, '',0,'Ben Arous');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1004,10,1004, '',0,'Bizerte');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1005,10,1005, '',0,'Gabès');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1006,10,1006, '',0,'Gafsa');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1007,10,1007, '',0,'Jendouba');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1008,10,1008, '',0,'Kairouan');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1009,10,1009, '',0,'Kasserine');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1010,10,1010, '',0,'Kébili');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1011,10,1011, '',0,'La Manouba');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1012,10,1012, '',0,'Le Kef');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1013,10,1013, '',0,'Mahdia');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1014,10,1014, '',0,'Médenine');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1015,10,1015, '',0,'Monastir');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1016,10,1016, '',0,'Nabeul');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1017,10,1017, '',0,'Sfax');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1018,10,1018, '',0,'Sidi Bouzid');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1019,10,1019, '',0,'Siliana');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1020,10,1020, '',0,'Sousse');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1021,10,1021, '',0,'Tataouine');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1022,10,1022, '',0,'Tozeur');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1023,10,1023, '',0,'Tunis');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1024,10,1024, '',0,'Zaghouan');

-- Regions d'Australie (id pays=28)
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (2801,28,2801,     '',0,'Australia');



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

-- Provinces de Belgique
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

-- Provinces Australie
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2801,'NSW','',1,'','New South Wales');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2801,'VIC','',1,'','Victoria');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2801,'QLD','',1,'','Queensland');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2801, 'SA','',1,'','South Australia');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2801,'ACT','',1,'','Australia Capital Territory');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2801,'TAS','',1,'','Tasmania');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2801, 'WA','',1,'','Western Australia');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2801, 'NT','',1,'','Northern Territory');


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
insert into llx_c_typent (id,code,libelle,active) values (  0, 'TE_UNKNOWN', '-',             1);
insert into llx_c_typent (id,code,libelle,active) values (  1, 'TE_STARTUP', 'Start-up',      0);
insert into llx_c_typent (id,code,libelle,active) values (  2, 'TE_GROUP',   'Grand groupe',  1);
insert into llx_c_typent (id,code,libelle,active) values (  3, 'TE_MEDIUM',  'PME/PMI',       1);
insert into llx_c_typent (id,code,libelle,active) values (  4, 'TE_SMALL',   'TPE',           1);
insert into llx_c_typent (id,code,libelle,active) values (  5, 'TE_ADMIN',   'Administration',1);
insert into llx_c_typent (id,code,libelle,active) values (  6, 'TE_WHOLE',   'Grossiste',     0);
insert into llx_c_typent (id,code,libelle,active) values (  7, 'TE_RETAIL',  'Revendeur',     0);
insert into llx_c_typent (id,code,libelle,active) values (  8, 'TE_PRIVATE', 'Particulier',   1);
insert into llx_c_typent (id,code,libelle,active) values (100, 'TE_OTHER',   'Autres',        1);


--
-- Devises (code secondaire - code ISO4217 - libelle fr)
--

insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'BT', 'THB', 1, 'Bath thailandais'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'CD', 'DKK', 1, 'Couronnes dannoises'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'CN', 'NOK', 1, 'Couronnes norvegiennes'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'CS', 'SEK', 1, 'Couronnes suedoises'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'CZ', 'CZK', 1, 'Couronnes tcheques'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'TD', 'TND', 1, 'Dinar tunisien'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'DA', 'DZD', 1, 'Dinar algérien'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'DH', 'MAD', 1, 'Dirham'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'AD', 'AUD', 1, 'Dollars australiens'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'DC', 'CAD', 1, 'Dollars canadiens'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'DH', 'HKD', 1, 'Dollars hong kong'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'DS', 'SGD', 1, 'Dollars singapour'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'DU', 'USD', 1, 'Dollars us'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'EC', 'XEU', 1, 'Ecus'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'ES', 'PTE', 0, 'Escudos'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'FB', 'BEF', 0, 'Francs belges'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'FF', 'FRF', 0, 'Francs francais'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'FL', 'LUF', 0, 'Francs luxembourgeois'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'FO', 'NLG', 1, 'Florins'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'FS', 'CHF', 1, 'Francs suisses'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'LI', 'IEP', 1, 'Livres irlandaises'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'LR', 'ITL', 0, 'Lires'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'LS', 'GBP', 1, 'Livres sterling'); 
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'MA', 'DEM', 0, 'Deutsch mark'); 
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
-- insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'CF', 'XOF', 1, 'Francs cfa bceao');	-- doublon sur code
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

-- AUSTRALIE (id 28)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (281,28,  '10','0','VAT Rate 10',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (282,28,   '0','0','VAT Rate 0',1);

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

-- GUADELOUPE (id 105)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 111, 105, '8.5','0','VAT Rate 8.5',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 112, 105, '8.5','1','VAT Rate 8.5 non perçu par le vendeur mais récupérable par l\'acheteur',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 113, 105,   '0','0','VAT Rate 0 ou non applicable',1);

-- MARTINIQUE (id 150)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 121, 150, '8.5','0','VAT Rate 8.5',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 122, 150, '8.5','1','VAT Rate 8.5 non perçu par le vendeur mais récupérable par l\'acheteur',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 123, 150,   '0','0','VAT Rate 0 ou non applicable',1);

-- REUNION (id 187)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 131, 187, '8.5','0','VAT Rate 8.5',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 132, 187, '8.5','1','VAT Rate 8.5 non perçu par le vendeur mais récupérable par l\'acheteur',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 133, 187,   '0','0','VAT Rate 0 ou non applicable',1);

-- PAYS-BAS (id 17)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (171,17,  '19','0','VAT Rate 19',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (172,17,   '6','0','VAT Rate 6',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (173,17,   '0','0','VAT Rate 0',1);

-- PORTUGAL (id 25)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (251,25,  '17','0','VAT Rate 17',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (252,25,  '12','0','VAT Rate 12',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (253,25,   '0','0','VAT Rate 0',1);

-- ROYAUME UNI (id 7)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 71, 7,'17.5','0','VAT Rate 17.5',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 72, 7,   '5','0','VAT Rate 5',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 73, 7,   '0','0','VAT Rate 0',1);

-- SUISSE (id 6)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 61, 6, '7.6','0','VAT Rate 7.6',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 62, 6, '3.6','0','VAT Rate 3.6',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 63, 6, '2.4','0','VAT Rate 2.4',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 64, 6,   '0','0','VAT Rate 0',1);

-- TUNISIE (id 10)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (101,10, '6','0','TVA 6%',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (102,10, '12','0','TVA 12%',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (103,10, '18','0','VAT 18%',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (104,10, '7.5','0','TVA 6% Majoré à 25% (7.5%)',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (105,10, '15','0','TVA 12% Majoré à 25% (15%)',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (106,10, '22.5','0','VAT 18% Majoré à 25% (22.5%)',1);


--
-- Les types de contact d'un element
--
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (10, 'contrat', 'internal', 'SALESREPSIGN',  'Commercial signataire du contrat', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (11, 'contrat', 'internal', 'SALESREPFOLL',  'Commercial suivi du contrat', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (20, 'contrat', 'external', 'BILLING',       'Contact client facturation contrat', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (21, 'contrat', 'external', 'CUSTOMER',      'Contact client suivi contrat', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (22, 'contrat', 'external', 'SALESREPSIGN',  'Contact client signataire contrat', 1);
                                                                                                    
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (31, 'propal',  'internal', 'SALESREPFOLL',  'Commercial à l\'origine de la propale', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (40, 'propal',  'external', 'BILLING',       'Contact client facturation propale', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (41, 'propal',  'external', 'CUSTOMER',      'Contact client suivi propale', 1);
                                                                                                    
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (80, 'projet',  'internal', 'PROJECTLEADER', 'Chef de Projet', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (81, 'projet',  'external', 'PROJECTLEADER', 'Chef de Projet', 1);

insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (50, 'facture', 'internal', 'SALESREPFOLL',  'Responsable suivi du paiement', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (60, 'facture', 'external', 'BILLING',       'Contact client facturation', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (61, 'facture', 'external', 'SHIPPING',      'Contact client livraison', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (62, 'facture', 'external', 'SERVICE',       'Contact client prestation', 1);

insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (91, 'commande','internal', 'SALESREPFOLL',  'Responsable suivi de la commande', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (100,'commande','external', 'BILLING',       'Contact client facturation commande', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (101,'commande','external', 'CUSTOMER',      'Contact client suivi commande', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (102,'commande','external', 'SHIPPING',      'Contact client livraison commande', 1);

insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (120, 'fichinter','internal', 'INTERREPFOLL',  'Responsable suivi de l\'intervention', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (121, 'fichinter','internal', 'INTERVENING',   'Intervenant', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (130, 'fichinter','external', 'BILLING',       'Contact client facturation intervention', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (131, 'fichinter','external', 'CUSTOMER',      'Contact client suivi de l\'intervention', 1);


--
-- Entree menu auguria
--

-- 
-- Contenu de la table `llx_menu`
-- 
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1, 'home', '', 0, '/index.php?mainmenu=home&leftmenu=', 'Home', -1, '', '', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2, 'companies', '', 0, '/index.php?mainmenu=companies&amp;leftmenu=', 'ThirdParties', -1, 'companies', '', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3, 'products', '', 0, '/product/index.php?mainmenu=products&amp;leftmenu=', 'Products/Services', -1, 'products', '$user->rights->produit->lire', '', 0, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4, 'suppliers', '', 0, '/fourn/index.php?mainmenu=suppliers&amp;leftmenu=', 'Suppliers', -1, 'suppliers', '$user->rights->fournisseur->lire', '', 0, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (5, 'commercial', '', 0, '/comm/index.php?mainmenu=commercial&amp;leftmenu=', 'Commercial', -1, 'commercial', '$user->rights->commercial->main->lire', '', 2, 5);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (6, 'accountancy', '', 0, '/compta/index.php?mainmenu=accountancy&amp;leftmenu=', 'MenuFinancial', -1, 'compta', '$user->rights->compta->resultat->lire || $user->rights->comptaexpert->plancompte->lire || $user->rights->commande->lire || $user->rights->facture->lire || $user->rights->banque->lire', '', 2, 6);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (7, 'project', '', 0, '/projet/index.php?mainmenu=project&amp;leftmenu=', 'Projects', -1, 'projects', '$user->rights->projet->lire', '', 0, 7);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (8, 'tools', '', 0, '/index.php?mainmenu=tools&amp;leftmenu=', 'Tools', -1, 'other', '$user->rights->mailing->lire || $user->rights->bookmark->lire || $user->rights->export->lire', '', 2, 8);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (9, 'telephony', '', 0, '/telephonie/index.php?mainmenu=telephony&amp;leftmenu=', 'Telephony', -1, 'telephony', '$user->rights->telephonie->lire', '', 2, 9);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (10, 'energy', '', 0, '/energie/index.php?mainmenu=energy&amp;leftmenu=', 'Energy', -1, 'energy', '', '', 2, 10);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (11, 'shop', '', 0, '/boutique/index.php?mainmenu=shop&amp;leftmenu=', 'OSCommerce', -1, 'shop', '', '', 0, 11);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (12, 'shop', '', 0, '/oscommerce_ws/index.php?mainmenu=shop&amp;leftmenu=', 'OSCommerce', -1, 'shop', '', '', 0, 12);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (13, 'webcal', '', 0, '/webcal/webcal.php?mainmenu=webcal&amp;leftmenu=', 'Calendar', -1, 'other', '', '', 0, 13);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (14, 'mantis', '', 0, '/mantis/mantis.php?mainmenu=mantis', 'BugTracker', -1, 'other', '', '', 2, 14);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (15, 'members', '', 0, '/adherents/index.php?mainmenu=members&amp;leftmenu=', 'Members', -1, 'members', '', '', 2, 15);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (16, 'phenix', '', 0, '/phenix/phenix.php?mainmenu=phenix&amp;leftmenu=', 'Calendar', -1, 'other', '', '', 0, 16);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (100, 'home', '', 1, '/admin/index.php?leftmenu=setup', 'Setup', 0, 'admin', '', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (101, 'home', '$leftmenu=="setup"', 100, '/admin/company.php', 'MenuCompanySetup', 1, 'admin', '', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (102, 'home', '$leftmenu=="setup"', 100, '/admin/ihm.php', 'GUISetup', 1, 'admin', '', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (103, 'home', '$leftmenu=="setup"', 100, '/admin/modules.php', 'Modules', 1, 'admin', '', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (104, 'home', '$leftmenu=="setup"', 100, '/admin/boxes.php', 'Boxes', 1, 'admin', '', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (105, 'home', '$leftmenu=="setup"', 100, '/admin/menus.php', 'Menus', 1, 'admin', '', '', 2, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (106, 'home', '$leftmenu=="setup"', 100, '/admin/delais.php', 'DelaysBeforeWarning', 1, 'admin', '', '', 2, 5);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (107, 'home', '$leftmenu=="setup"', 100, '/admin/triggers.php', 'Triggers', 1, 'admin', '', '', 2, 6);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (108, 'home', '$leftmenu=="setup"', 100, '/admin/perms.php', 'Security', 1, 'admin', '', '', 2, 7);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (109, 'home', '$leftmenu=="setup"', 100, '/admin/mails.php', 'Emails', 1, 'admin', '', '', 2, 8);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (110, 'home', '$leftmenu=="setup"', 100, '/admin/limits.php', 'Limits', 1, 'admin', '', '', 2, 9);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (111, 'home', '$leftmenu=="setup"', 100, '/admin/dict.php', 'DictionnarySetup', 1, 'admin', '', '', 2, 10);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (112, 'home', '$leftmenu=="setup"', 100, '/admin/const.php', 'OtherSetup', 1, 'admin', '', '', 2, 11);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (200, 'home', '', 1, '/admin/system/index.php?leftmenu=system', 'SystemInfo', 0, 'admin', '', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (201, 'home', '$leftmenu=="system"', 200, '/admin/system/dolibarr.php', 'Dolibarr', 1, 'admin', '', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (202, 'home', '$leftmenu=="system"', 201, '/admin/system/constall.php', 'AllParameters', 2, 'admin', '', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (203, 'home', '$leftmenu=="system"', 201, '/about.php', 'About', 2, 'admin', '', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (204, 'home', '$leftmenu=="system"', 200, '/admin/system/os.php', 'OS', 1, 'admin', '', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (205, 'home', '$leftmenu=="system"', 200, '/admin/system/web.php', 'WebServer', 1, 'admin', '', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (206, 'home', '$leftmenu=="system"', 200, '/admin/system/phpinfo.php', 'Php', 1, 'admin', '', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (207, 'home', '$leftmenu=="system"', 206, '/admin/system/phpinfo.php?what=conf', 'PhpConf', 2, 'admin', '', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (208, 'home', '$leftmenu=="system"', 206, '/admin/system/phpinfo.php?what=env', 'PhpEnv', 2, 'admin', '', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (209, 'home', '$leftmenu=="system"', 206, '/admin/system/phpinfo.php?what=modules', 'PhpModules', 2, 'admin', '', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (210, 'home', '$leftmenu=="system"', 200, '/admin/system/database.php', 'Database', 1, 'admin', '', '', 2, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (211, 'home', '$leftmenu=="system"', 210, '/admin/system/database-tables.php', 'Tables', 2, 'admin', '', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (212, 'home', '$leftmenu=="system"', 210, '/admin/system/database-tables-contraintes.php', 'Constraints', 2, 'admin', '', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (300, 'home', '', 1, '/admin/tools/index.php?leftmenu=admintools', 'SystemTools', 0, 'admin', '', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (301, 'home', '$leftmenu=="admintools"', 300, '/admin/tools/dolibarr_export.php', 'Backup', 1, 'admin', '', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (302, 'home', '$leftmenu=="admintools"', 300, '/admin/tools/dolibarr_import.php', 'Restore', 1, 'admin', '', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (303, 'home', '$leftmenu=="admintools"', 300, '/admin/tools/purge.php', 'Purge', 1, 'admin', '', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (304, 'home', '$leftmenu=="admintools"', 300, '/admin/tools/eaccelerator.php', 'EAccelerator', 1, 'admin', '', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (400, 'home', '', 1, '/user/home.php?leftmenu=users', 'MenuUsersAndGroups', 0, 'users', '', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (401, 'home', '$leftmenu=="users"', 400, '/user/index.php', 'Users', 1, 'users', '$user->rights->user->user->lire || $user->admin', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (402, 'home', '$leftmenu=="users"', 401, '/user/fiche.php?action=create', 'NewUser', 2, 'users', '$user->rights->user->user->creer || $user->admin', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (403, 'home', '$leftmenu=="users"', 400, '/user/group/index.php', 'Groups', 1, 'users', '$user->rights->user->user->lire || $user->admin', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (404, 'home', '$leftmenu=="users"', 403, '/user/group/fiche.php?action=create', 'NewGroup', 2, 'users', '$user->rights->user->user->creer || $user->admin', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (500, 'companies', '', 2, '/societe.php', 'ThirdParty', 0, 'companies', '$user->rights->societe->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (501, 'companies', '', 500, '/soc.php?action=create', 'MenuNewThirdParty', 1, 'companies', '$user->rights->societe->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (502, 'companies', '', 500, '/societe/groupe/index.php', 'MenuSocGroup', 1, 'companies', '$user->rights->societe->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (503, 'companies', '', 500, '/fourn/liste.php?leftmenu=suppliers', 'Suppliers', 1, 'suppliers', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (504, 'companies', '', 503, '/soc.php?leftmenu=supplier&action=create&type=f', 'NewSupplier', 2, 'suppliers', '$user->rights->societe->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (505, 'companies', '', 503, '/contact/index.php?leftmenu=suppliers&type=f', 'Contacts', 2, 'suppliers', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (506, 'companies', '', 500, '/comm/prospect/prospects.php?leftmenu=prospects', 'Prospects', 1, 'companies', '$user->rights->societe->lire', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (507, 'companies', '', 506, '/soc.php?leftmenu=prospects&action=create&type=p', 'MenuNewProspect', 2, 'companies', '$user->rights->societe->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (508, 'companies', '', 506, '/contact/index.php?leftmenu=customers&type=p', 'Contacts', 2, 'companies', '$user->rights->societe->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (509, 'companies', '', 500, '/comm/clients.php?leftmenu=customers', 'Customers', 1, 'companies', '$user->rights->societe->lire', '', 2, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (510, 'companies', '', 509, '/soc.php?leftmenu=customers&action=create&type=c', 'MenuNewCustomer', 2, 'companies', '$user->rights->societe->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (511, 'companies', '', 509, '/contact/index.php?leftmenu=customers&type=c', 'Contacts', 2, 'companies', '$user->rights->societe->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (600, 'companies', '', 2, '/contact/index.php?leftmenu=contacts', 'Contacts', 0, 'companies', '$user->rights->societe->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (601, 'companies', '', 600, '/contact/fiche.php?leftmenu=contacts&action=create', 'NewContact', 1, 'companies', '$user->rights->societe->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (602, 'companies', '', 600, '/contact/index.php?leftmenu=contacts', 'List', 1, 'companies', '$user->rights->societe->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (700, 'commercial', '', 5, '/comm/prospect/index.php?leftmenu=prospects', 'Prospects', 0, 'companies', '$user->rights->societe->lire', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (701, 'commercial', '', 700, '/soc.php?leftmenu=prospects&action=create&type=c', 'MenuNewProspect', 1, 'companies', '$user->rights->societe->creer', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (702, 'commercial', '', 700, '/contact/index.php?leftmenu=prospects&type=p', 'List', 1, 'companies', '$user->rights->societe->lire', '', 0, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (703, 'commercial', '$leftmenu=="prospects"', 702, '/comm/prospect/prospects.php?sortfield=s.datec&sortorder=desc&begin=&stcomm=-1', 'LastProspectDoNotContact', 2, 'companies', '$user->rights->societe->lire', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (704, 'commercial', '$leftmenu=="prospects"', 702, '/comm/prospect/prospects.php?sortfield=s.datec&sortorder=desc&begin=&stcomm=0', 'LastProspectNeverContacted', 2, 'companies', '$user->rights->societe->lire', '', 0, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (705, 'commercial', '$leftmenu=="prospects"', 702, '/comm/prospect/prospects.php?sortfield=s.datec&sortorder=desc&begin=&stcomm=1', 'LastProspectToContact', 2, 'companies', '$user->rights->societe->lire', '', 0, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (706, 'commercial', '$leftmenu=="prospects"', 702, '/comm/prospect/prospects.php?sortfield=s.datec&sortorder=desc&begin=&stcomm=2', 'LastProspectContactInProcess', 2, 'companies', '$user->rights->societe->lire', '', 0, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (707, 'commercial', '$leftmenu=="prospects"', 702, '/comm/prospect/prospects.php?sortfield=s.datec&sortorder=desc&begin=&stcomm=3', 'LastProspectContactDone', 2, 'companies', '$user->rights->societe->lire', '', 0, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (708, 'commercial', '', 700, '/contact/index.php?leftmenu=prospects&type=p', 'Contacts', 1, 'companies', '$user->rights->societe->lire', '', 0, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (800, 'commercial', '', 5, '/comm/index.php?leftmenu=customers', 'Customers', 0, 'companies', '$user->rights->societe->lire', '', 0, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (801, 'commercial', '', 800, '/soc.php?leftmenu=customers&action=create&type=c', 'MenuNewCustomer', 1, 'companies', '$user->rights->societe->creer', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (802, 'commercial', '', 800, '/comm/clients.php?leftmenu=customers', 'List', 1, 'companies', '$user->rights->societe->lire', '', 0, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (803, 'commercial', '', 800, '/contact/index.php?leftmenu=customers&type=c', 'Contacts', 1, 'companies', '$user->rights->societe->lire', '', 0, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (900, 'commercial', '', 5, '/contact/index.php?leftmenu=contacts', 'Contacts', 0, 'companies', '$user->rights->societe->lire', '', 0, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (901, 'commercial', '', 900, '/contact/fiche.php?leftmenu=contacts&action=create', 'NewContact', 1, 'companies', '$user->rights->societe->creer', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (902, 'commercial', '', 900, '/contact/index.php?leftmenu=contacts&action=create', 'List', 1, 'companies', '$user->rights->societe->lire', '', 0, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1000, 'commercial', '', 5, '/comm/action/index.php?leftmenu=actions', 'Actions', 0, 'companies', '$user->rights->societe->lire', '', 0, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1001, 'commercial', '$leftmenu=="actions"', 1000, '/societe.php?leftmenu=actions', 'NewAction', 1, 'companies', '$user->rights->societe->lire', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1002, 'commercial', '$leftmenu=="actions"', 1000, '/comm/action/index.php?leftmenu=actions&status=todo', 'MenuToDoActions', 1, 'companies', '$user->rights->societe->lire', '', 0, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1003, 'commercial', '$leftmenu=="actions"', 1000, '/comm/action/index.php?leftmenu=actions&time=today', 'Today', 1, 'companies', '$user->rights->societe->lire', '', 0, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1004, 'commercial', '$leftmenu=="actions"', 1000, '/comm/action/rapport/index.php?leftmenu=actions', 'Reportings', 1, 'companies', '$user->rights->societe->lire', '', 0, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1100, 'commercial', '', 5, '/comm/propal.php?leftmenu=propals', 'Prop', 0, 'propal', '$user->rights->propale->lire', '', 2, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1101, 'commercial', '$leftmenu=="propals"', 1100, '/societe.php?leftmenu=propals', 'NewPropal', 1, 'propal', '$user->rights->propale->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1102, 'commercial', '$leftmenu=="propals"', 1100, '/comm/propal.php?viewstatut=0', 'PropalsDraft', 1, 'propal', '$user->rights->propale->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1103, 'commercial', '$leftmenu=="propals"', 1100, '/comm/propal.php?viewstatut=1', 'PropalsOpened', 1, 'propal', '$user->rights->propale->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1104, 'commercial', '$leftmenu=="propals"', 1100, '/comm/propal.php?viewstatut=2,3,4', 'PropalStatusClosedShort', 1, 'propal', '$user->rights->propale->lire', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1105, 'commercial', '$leftmenu=="propals"', 1100, '/comm/propal/stats/index.php?leftmenu=propals', 'Statistics', 1, 'propal', '$user->rights->propale->lire', '', 2, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1200, 'commercial', '', 5, '/commande/index.php?leftmenu=orders', 'Orders', 0, 'orders', '$user->rights->commande->lire', '', 2, 5);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1201, 'commercial', '$leftmenu=="orders"', 1200, '/societe.php?leftmenu=orders', 'NewOrder', 1, 'orders', '$user->rights->commande->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1202, 'commercial', '$leftmenu=="orders"', 1200, '/commande/liste.php?leftmenu=orders&viewstatut=0', 'StatusOrderDraftShort', 1, 'orders', '$user->rights->commande->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1203, 'commercial', '$leftmenu=="orders"', 1200, '/commande/liste.php?leftmenu=orders&viewstatut=1', 'StatusOrderValidated', 1, 'orders', '$user->rights->commande->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1204, 'commercial', '$leftmenu=="orders"', 1200, '/commande/liste.php?leftmenu=orders&viewstatut=2', 'StatusOrderOnProcessShort', 1, 'orders', '$user->rights->commande->lire', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1205, 'commercial', '$leftmenu=="orders"', 1200, '/commande/liste.php?leftmenu=orders&viewstatut=3', 'StatusOrderToBill', 1, 'orders', '$user->rights->commande->lire', '', 2, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1206, 'commercial', '$leftmenu=="orders"', 1200, '/commande/liste.php?leftmenu=orders&viewstatut=4', 'StatusOrderProcessed', 1, 'orders', '$user->rights->commande->lire', '', 2, 5);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1207, 'commercial', '$leftmenu=="orders"', 1200, '/commande/liste.php?leftmenu=orders&viewstatut=-1', 'StatusOrderCanceledShort', 1, 'orders', '$user->rights->commande->lire', '', 2, 6);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1208, 'commercial', '$leftmenu=="orders"', 1200, '/commande/stats/index.php?leftmenu=orders', 'Statistics', 1, 'orders', '$user->rights->commande->lire', '', 2, 7);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1300, 'commercial', '', 5, '/expedition/index.php?leftmenu=sendings', 'Sendings', 0, 'orders', '$user->rights->expedition->lire', '', 2, 6);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1301, 'commercial', '$leftmenu=="sendings"', 1300, '/expedition/liste.php?leftmenu=sendings', 'List', 1, 'orders', '$user->rights->expedition->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1302, 'commercial', '$leftmenu=="sendings"', 1300, '/expedition/stats/index.php?leftmenu=sendings', 'Statistics', 1, 'orders', '$user->rights->expedition->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1400, 'commercial', '', 5, '/contrat/index.php?leftmenu=contracts', 'Contracts', 0, 'contracts', '$user->rights->contrat->lire', '', 2, 7);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1401, 'commercial', '$leftmenu=="contracts"', 1400, '/societe.php?leftmenu=contracts', 'NewContract', 1, 'contracts', '$user->rights->contrat->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1402, 'commercial', '$leftmenu=="contracts"', 1400, '/contrat/liste.php?leftmenu=contracts', 'List', 1, 'contracts', '$user->rights->contrat->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1403, 'commercial', '$leftmenu=="contracts"', 1400, '/contrat/services.php?leftmenu=contracts', 'MenuServices', 1, 'contracts', '$user->rights->contrat->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1404, 'commercial', '$leftmenu=="contracts"', 1402, '/contrat/services.php?leftmenu=contracts&mode=0', 'MenuInactiveServices', 2, 'contracts', '$user->rights->contrat->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1405, 'commercial', '$leftmenu=="contracts"', 1402, '/contrat/services.php?leftmenu=contracts&mode=4', 'MenuRunningServices', 2, 'contracts', '$user->rights->contrat->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1406, 'commercial', '$leftmenu=="contracts"', 1402, '/contrat/services.php?leftmenu=contracts&mode=4&filter=expired', 'MenuExpiredServices', 2, 'contracts', '$user->rights->contrat->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1407, 'commercial', '$leftmenu=="contracts"', 1402, '/contrat/services.php?leftmenu=contracts&mode=5', 'MenuClosedServices', 2, 'contracts', '$user->rights->contrat->lire', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1500, 'commercial', '', 5, '/fichinter/index.php?leftmenu=ficheinter', 'Interventions', 0, 'interventions', '$user->rights->ficheinter->lire', '', 2, 8);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1501, 'commercial', '$leftmenu=="ficheinter"', 1500, '/fichinter/fiche.php?action=create&leftmenu=ficheinter', 'NewIntervention', 1, 'interventions', '$user->rights->ficheinter->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1502, 'commercial', '$leftmenu=="ficheinter"', 1500, '/fichinter/index.php?leftmenu=ficheinter', 'List', 1, 'interventions', '$user->rights->ficheinter->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1600, 'accountancy', '', 6, '/compta/index.php?leftmenu=suppliers', 'Suppliers', 0, 'companies', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1601, 'accountancy', '', 1600, '/soc.php?leftmenu=suppliers&action=create&type=f', 'NewSupplier', 1, 'companies', '$user->rights->societe->creer && $user->rights->fournisseur->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1602, 'accountancy', '', 1600, '/fourn/liste.php?leftmenu=suppliers', 'List', 1, 'companies', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1603, 'accountancy', '', 1600, '/contact/index.php?leftmenu=suppliers&type=f', 'Contacts', 1, 'companies', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1604, 'accountancy', '', 1600, '/fourn/facture/index.php?leftmenu=suppliers_bills', 'BillsSuppliers', 1, 'bills', '$user->rights->fournisseur->facture->lire', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1605, 'accountancy', '$leftmenu=="suppliers_bills"', 1604, '/fourn/facture/fiche.php?action=create', 'NewBill', 2, 'bills', '$user->rights->fournisseur->facture->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1606, 'accountancy', '$leftmenu=="suppliers_bills"', 1604, '/fourn/facture/impayees.php', 'Unpayed', 2, 'bills', '$user->rights->fournisseur->facture->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1607, 'accountancy', '$leftmenu=="suppliers_bills"', 1604, '/fourn/facture/paiement.php', 'Payments', 2, 'bills', '$user->rights->fournisseur->facture->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1700, 'accountancy', '', 6, '/compta/index.php?leftmenu=customers', 'Customers', 0, 'companies', '$user->rights->societe->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1701, 'accountancy', '', 1700, '/soc.php?leftmenu=customers&action=create&type=c', 'MenuNewCustomer', 1, 'companies', '$user->rights->societe->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1702, 'accountancy', '', 1700, '/compta/clients.php?leftmenu=customers', 'List', 1, 'companies', '$user->rights->societe->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1703, 'accountancy', '', 1700, '/contact/index.php?leftmenu=customers&type=c', 'Contacts', 1, 'companies', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1704, 'accountancy', '', 1700, '/compta/facture.php?leftmenu=customers_bills', 'BillsCustomers', 1, 'bills', '$user->rights->facture->lire', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1705, 'accountancy', 'eregi("customers_bills",$leftmenu)', 1704, '/compta/clients.php?action=facturer&leftmenu=customers_bills', 'NewBill', 2, 'bills', '$user->rights->facture->creer', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1706, 'accountancy', 'eregi("customers_bills",$leftmenu)', 1704, '/compta/facture/fiche-rec.php?leftmenu=customers_bills', 'Repeatable', 2, 'bills', '$user->rights->facture->lire', '', 2, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1707, 'accountancy', 'eregi("customers_bills",$leftmenu)', 1704, '/compta/facture/impayees.php?action=facturer&leftmenu=customers_bills', 'Unpayed', 2, 'bills', '$user->rights->facture->lire', '', 2, 5);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1708, 'accountancy', 'eregi("customers_bills",$leftmenu)', 1704, '/compta/paiement/liste.php?leftmenu=customers_bills_payments', 'Payments', 2, 'bills', '$user->rights->facture->lire', '', 2, 6);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1709, 'accountancy', 'eregi("customers_bills_payments",$leftmenu)', 1708, '/compta/paiement/avalider.php?leftmenu=customers_bills_payments', 'MenuToValid', 3, 'bills', '$user->rights->facture->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1710, 'accountancy', 'eregi("customers_bills_payments",$leftmenu)', 1708, '/compta/paiement/rapport.php?leftmenu=customers_bills_payments', 'Reportings', 3, 'bills', '$user->rights->facture->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1711, 'accountancy', '', 6, '/compta/paiement/cheque/index.php?leftmenu=checks', 'MenuChequeDeposits', 0, 'bills', '$user->rights->facture->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1712, 'accountancy', 'eregi("checks",$leftmenu)', 1711, '/compta/paiement/cheque/fiche.php?leftmenu=checks&action=new', 'NewCheckDeposit', 1, 'bills', '$user->rights->facture->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1713, 'accountancy', 'eregi("checks",$leftmenu)', 1711, '/compta/paiement/cheque/liste.php?leftmenu=checks', 'MenuChequesReceipts', 1, 'bills', '$user->rights->facture->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1714, 'accountancy', 'eregi("customers_bills",$leftmenu)', 1704, '/compta/facture/stats/index.php?leftmenu=customers_bills', 'Statistics', 2, 'bills', '$user->rights->facture->lire', '', 2, 8);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1715, 'accountancy', '', 1700, '/compta/paiement/cheque/index.php', 'CheckReceipt', 1, 'bills', '$user->rights->facture->lire', '', 1, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1716, 'accountancy', '', 1704, '/compta/paiement/cheque/fiche.php?action=new', 'New', 2, 'bills', '$user->rights->facture->lire', '', 1, 9);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1717, 'accountancy', '', 1704, '/compta/paiement/cheque/liste.php', 'List', 2, 'bills', '$user->rights->facture->lire', '', 1, 10);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1800, 'accountancy', '', 6, '/compta/propal.php', 'Prop', 0, 'propal', '$user->rights->propale->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (1900, 'accountancy', '', 6, '/compta/commande/liste.php?leftmenu=orders&status=3&afacturer=1', 'MenuOrdersToBill', 0, 'orders', '$user->rights->commande->lire', '', 0, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2000, 'accountancy', '', 6, '/compta/dons/index.php?leftmenu=donations&mainmenu=accountancy', 'Donations', 0, 'donations', '$user->rights->don->lire', '', 2, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2001, 'accountancy', '$leftmenu=="donations"', 2000, '/compta/dons/fiche.php?action=create', 'NewDonation', 1, 'donations', '$user->rights->don->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2002, 'accountancy', '$leftmenu=="donations"', 2000, '/compta/dons/liste.php?action=create', 'List', 1, 'donations', '$user->rights->don->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2003, 'accountancy', '$leftmenu=="donations"', 2000, '/compta/dons/stats.php', 'Statistics', 1, 'donations', '$user->rights->don->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2100, 'accountancy', '', 6, '/compta/deplacement/index.php', 'Trips', 0, 'trips', '$user->rights->deplacement->lire', '', 0, 5);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2200, 'accountancy', '', 6, '/compta/charges/index.php?leftmenu=charges&mainmenu=accountancy', 'Charges', 0, 'Charges', '$user->rights->tax->charges->lire', '', 0, 6);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2201, 'accountancy', '$leftmenu=="charges"', 2200, '/compta/sociales/index.php', 'SocialContributions', 1, '', '$user->rights->tax->charges->lire', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2300, 'accountancy', '', 6, '/compta/tva/index.php?leftmenu=vat&mainmenu=accountancy', 'VAT', 0, 'companies', '$user->rights->tax->charges->lire', '', 0, 7);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2301, 'accountancy', '$leftmenu=="vat"', 2300, '/compta/tva/fiche.php?action=create', 'NewPayment', 1, 'companies', '$user->rights->tax->charges->creer', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2302, 'accountancy', '$leftmenu=="vat"', 2300, '/compta/tva/reglement.php', 'Payments', 1, 'companies', '$user->rights->tax->charges->lire', '', 0, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2303, 'accountancy', '$leftmenu=="vat"', 2300, '/compta/tva/clients.php', 'ReportByCustomers', 1, 'companies', '$user->rights->tax->charges->lire', '', 0, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2400, 'accountancy', '', 6, '/compta/ventilation/index.php?leftmenu=ventil', 'Ventilation', 0, 'companies', '$user->rights->compta->ventilation->lire', '', 0, 8);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2401, 'accountancy', '$leftmenu=="ventil"', 2400, '/compta/ventilation/liste.php', 'A ventiler', 1, 'companies', '$user->rights->compta->ventilation->lire', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2402, 'accountancy', '$leftmenu=="ventil"', 2400, '/compta/ventilation/lignes.php', 'Ventilées', 1, 'companies', '$user->rights->compta->ventilation->lire', '', 0, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2403, 'accountancy', '$leftmenu=="ventil"', 2400, '/compta/param/', 'Setup', 1, 'companies', '$user->rights->compta->ventilation->parametrer', '', 0, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2404, 'accountancy', '$leftmenu=="ventil"', 2403, '/compta/param/comptes/liste.php', 'List', 2, 'companies', '$user->rights->compta->ventilation->parametrer', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2405, 'accountancy', '$leftmenu=="ventil"', 2403, '/compta/param/comptes/fiche.php?action=create', 'New', 2, 'companies', '$user->rights->compta->ventilation->parametrer', '', 0, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2406, 'accountancy', '$leftmenu=="ventil"', 2400, '/compta/export/', 'Export', 1, 'companies', '$user->rights->compta->ventilation->lire', '', 0, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2407, 'accountancy', '$leftmenu=="ventil"', 2406, '/compta/export/index.php', 'New', 2, 'companies', '$user->rights->compta->ventilation->lire', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2408, 'accountancy', '$leftmenu=="ventil"', 2406, '/compta/export/liste.php', 'List', 2, 'companies', '$user->rights->compta->ventilation->lire', '', 0, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2500, 'accountancy', '', 6, '/compta/prelevement/index.php?leftmenu=withdraw', 'StandingOrders', 0, 'withdrawals', '$user->rights->prelevement->bons->lire', '', 2, 9);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2501, 'accountancy', '$leftmenu=="withdraw"', 2500, '/compta/prelevement/demandes.php?status=0', 'StandingOrderToProcess', 1, 'withdrawals', '$user->rights->prelevement->bons->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2502, 'accountancy', '$leftmenu=="withdraw"', 2500, '/compta/prelevement/create.php', 'NewStandingOrder', 1, 'withdrawals', '$user->rights->prelevement->bons->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2503, 'accountancy', '$leftmenu=="withdraw"', 2500, '/compta/prelevement/bons.php', 'WithdrawalsReceipts', 1, 'withdrawals', '$user->rights->prelevement->bons->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2504, 'accountancy', '$leftmenu=="withdraw"', 2500, '/compta/prelevement/liste.php', 'WithdrawalsLines', 1, 'withdrawals', '$user->rights->prelevement->bons->lire', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2505, 'accountancy', '$leftmenu=="withdraw"', 2500, '/compta/prelevement/liste_factures.php', 'WithdrawedBills', 1, 'withdrawals', '$user->rights->prelevement->bons->lire', '', 2, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2506, 'accountancy', '$leftmenu=="withdraw"', 2500, '/compta/prelevement/rejets.php', 'Rejects', 1, 'withdrawals', '$user->rights->prelevement->bons->lire', '', 2, 5);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2507, 'accountancy', '$leftmenu=="withdraw"', 2500, '/compta/prelevement/stats.php', 'Statistics', 1, 'withdrawals', '$user->rights->prelevement->bons->lire', '', 2, 6);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2508, 'accountancy', '$leftmenu=="withdraw"', 2500, '/compta/prelevement/config.php', 'Setup', 1, 'withdrawals', '$user->rights->prelevement->bons->configurer', '', 2, 7);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2600, 'accountancy', '', 6, '/compta/bank/index.php?leftmenu=bank', 'MenuBankCash', 0, 'banks', '$user->rights->banque->lire', '', 0, 10);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2601, 'accountancy', '$leftmenu=="bank"', 2600, '/compta/bank/fiche.php?action=create', 'MenuNewFinancialAccount', 1, 'banks', '$user->rights->banque->configurer', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2602, 'accountancy', '$leftmenu=="bank"', 2600, '/compta/bank/categ.php', 'Categories', 1, 'banks', '$user->rights->banque->configurer', '', 0, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2603, 'accountancy', '$leftmenu=="bank"', 2600, '/compta/bank/search.php', 'SearchTransaction', 1, 'banks', '$user->rights->banque->lire', '', 0, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2604, 'accountancy', '$leftmenu=="bank"', 2600, '/compta/bank/budget.php', 'ByCategories', 1, 'banks', '$user->rights->banque->lire', '', 0, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2605, 'accountancy', '$leftmenu=="bank"', 2600, '/compta/bank/bilan.php', 'Bilan', 1, 'banks', '$user->rights->banque->lire', '', 0, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2606, 'accountancy', '$leftmenu=="bank"', 2600, '/compta/bank/virement.php', 'BankTransfers', 1, 'banks', '$user->rights->banque->modifier', '', 0, 5);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2607, 'accountancy', '$leftmenu=="bank"', 2600, '/compta/bank/bplc.php', 'Transactions BPLC', 1, 'banks', '', '', 0, 6);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2700, 'accountancy', '', 6, '/compta/resultat/index.php?leftmenu=ca&mainmenu=accountancy', 'Reportings', 0, 'main', '$user->rights->compta->resultat->lire || $user->rights->comptaexpert->comptarapport->lire', '', 0, 11);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2701, 'accountancy', '$leftmenu=="ca"', 2700, '/compta/resultat/index.php?leftmenu=ca', 'Résultat / Exercice', 1, 'main', '$user->rights->compta->resultat->lire || $user->rights->comptaexpert->comptarapport->lire', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2702, 'accountancy', '$leftmenu=="ca"', 2701, '/compta/resultat/clientfourn.php?leftmenu=ca', 'ByCompanies', 2, 'main', '$user->rights->compta->resultat->lire || $user->rights->comptaexpert->comptarapport->lire', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2703, 'accountancy', '$leftmenu=="ca"', 2700, '/compta/stats/index.php?leftmenu=ca', 'Chiffre d''affaire', 1, 'main', '$user->rights->compta->resultat->lire || $user->rights->comptaexpert->comptarapport->lire', '', 0, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2704, 'accountancy', '$leftmenu=="ca"', 2703, '/compta/stats/casoc?leftmenu=ca', 'ByCompanies', 2, 'main', '$user->rights->compta->resultat->lire || $user->rights->comptaexpert->comptarapport->lire', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2705, 'accountancy', '$leftmenu=="ca"', 2703, '/compta/stats/cabyuser.php?leftmenu=ca', 'ByUsers', 2, 'main', '$user->rights->compta->resultat->lire || $user->rights->comptaexpert->comptarapport->lire', '', 0, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2800, 'products', '', 3, '/product/index.php?leftmenu=product&type=0', 'Products', 0, 'products', '$user->rights->produit->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2801, 'products', '', 2800, '/product/fiche.php?leftmenu=product&action=create&type=0', 'NewProduct', 1, 'products', '$user->rights->produit->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2802, 'products', '', 2800, '/product/liste.php?leftmenu=product&type=0', 'List', 1, 'products', '$user->rights->produit->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2803, 'products', '', 2800, '/product/reassort.php?type=0', 'Stocks', 1, 'products', '$user->rights->stock->lire', '', 2, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2804, 'products', '', 2800, '/product/fiche.php?leftmenu=product&action=create&type=0&canvas=livre', 'Nouveau livre', 1, 'products', '$user->rights->produit->creer', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2805, 'products', '', 2800, '/product/liste.php?leftmenu=product&type=0&canvas=livre', 'Livre', 1, 'products', '$user->rights->produit->lire', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2900, 'products', '', 3, '/product/index.php?leftmenu=service&type=1', 'Services', 0, 'products', '$user->rights->produit->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2901, 'products', '', 2900, '/product/fiche.php?leftmenu=service&action=create&type=1', 'NewService', 1, 'products', '$user->rights->produit->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (2902, 'products', '', 2900, '/product/liste.php?leftmenu=service&type=1', 'List', 1, 'products', '$user->rights->produit->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3000, 'products', '', 3, '/product/stats/index.php?leftmenu=stats', 'Statistics', 0, 'main', '$user->rights->produit>lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3001, 'products', '', 3000, '/product/popuprop.php?leftmenu=stats', 'Popularity', 1, 'main', '$user->rights->produit>lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3100, 'products', '', 3, '/product/stock/index.php?leftmenu=stock', 'Stock', 0, 'stocks', '$user->rights->stock->lire', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3101, 'products', '$leftmenu=="stock"', 3100, '/product/stock/fiche.php?action=create', 'MenuNewWarehouse', 1, 'stocks', '$user->rights->stock->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3102, 'products', '$leftmenu=="stock"', 3100, '/product/stock/liste.php', 'List', 1, 'stocks', '$user->rights->stock->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3103, 'products', '$leftmenu=="stock"', 3100, '/product/stock/valo.php', 'EnhancedValue', 1, 'stocks', '$user->rights->stock->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3104, 'products', '$leftmenu=="stock"', 3100, '/product/stock/mouvement.php', 'Movements', 1, 'stocks', '$user->rights->stock->mouvement->lire', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3200, 'products', '', 3, '/categories/index.php?leftmenu=cat&type=0', 'Categories', 0, 'categories', '$user->rights->categorie>lire', '', 2, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3201, 'products', '$leftmenu=="cat"', 3200, '/categories/fiche.php?action=create&type=0', 'NewCat', 1, 'categories', '$user->rights->categorie>creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3300, 'suppliers', '', 4, '/fourn/index.php?leftmenu=suppliers', 'Suppliers', 0, 'suppliers', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3301, 'suppliers', '', 3300, '/soc.php?leftmenu=suppliers&action=create&type=f', 'NewSupplier', 1, 'suppliers', '$user->rights->societe->creer && $user->rights->fournisseur->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3302, 'suppliers', '', 3300, '/fourn/liste.php', 'List', 1, 'suppliers', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3303, 'suppliers', '', 3300, '/contact/index.php?leftmenu=supplier&type=f', 'Contacts', 1, 'suppliers', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3304, 'suppliers', '', 3300, '/fourn/stats.php', 'Statistics', 1, 'suppliers', '$user->rights->societe->lire && $user->rights->fournisseur->lire', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3400, 'suppliers', '', 4, '/fourn/facture/index.php', 'Bills', 0, 'bills', '$user->rights->fournisseur->facture->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3401, 'suppliers', '', 3400, '/fourn/facture/fiche.php?action=create', 'NewBill', 1, 'bills', '$user->rights->fournisseur->facture->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3402, 'suppliers', '', 3400, '/fourn/facture/paiement.php', 'Payments', 1, 'bills', '$user->rights->fournisseur->facture->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3500, 'suppliers', '', 4, '/fourn/commande/index.php?leftmenu=suppliers', 'Orders', 0, 'orders', '$user->rights->fournisseur->commande->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3501, 'suppliers', '', 3500, '/societe.php?leftmenu=supplier', 'NewOrder', 1, 'orders', '$user->rights->fournisseur->commande->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3502, 'suppliers', '', 3500, '/fourn/commande/liste.php?leftmenu=suppliers', 'List', 1, 'orders', '$user->rights->fournisseur->commande->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3600, 'project', '', 7, '/projet/index.php?leftmenu=projects', 'Projects', 0, 'projects', '$user->rights->projet->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3601, 'project', '', 3600, '/comm/clients.php?leftmenu=projects', 'NewProject', 1, 'projects', '$user->rights->projet->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3602, 'project', '', 3600, '/projet/liste.php?leftmenu=projects', 'List', 1, 'projects', '$user->rights->projet->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3700, 'project', '', 7, '/projet/tasks', 'Tasks', 0, 'projects', '$user->rights->projet->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3701, 'project', '', 3700, '/projet/tasks/mytasks.php', 'Mytasks', 1, 'projects', '$user->rights->projet->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3800, 'project', '', 7, '/projet/activity', 'Activity', 0, 'projects', '$user->rights->projet->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3801, 'project', '', 3800, '/projet/activity/myactivity.php', 'MyActivity', 1, 'projects', '$user->rights->projet->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3900, 'tools', '', 8, '/comm/mailing/index.php?leftmenu=mailing', 'EMailings', 0, 'mails', '$user->rights->mailing->lire', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3901, 'tools', '', 3900, '/comm/mailing/fiche.php?leftmenu=mailing&action=create', 'NewMailing', 1, 'mails', '$user->rights->mailing->creer', '', 0, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (3902, 'tools', '', 3900, '/comm/mailing/fiche.php?leftmenu=mailing', 'List', 1, 'mails', '$user->rights->mailing->lire', '', 0, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4000, 'tools', '', 8, '/bookmarks/liste.php?leftmenu=bookmarks', 'Bookmarks', 0, 'other', '$user->rights->bookmark->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4001, 'tools', '', 4000, '/bookmarks/fiche.php?action=create', 'NewBookmark', 1, 'other', '$user->rights->bookmark->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4002, 'tools', '', 4000, '/bookmarks/liste.php', 'List', 1, 'other', '$user->rights->bookmark->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4100, 'tools', '', 8, '/exports/index.php?leftmenu=export', 'FormatedExport', 0, 'exports', '$user->rights->export->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4101, 'tools', '', 4100, '/exports/export.php?leftmenu=export', 'NewExport', 1, 'exports', '$user->rights->export->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4130, 'tools', '', 8, '/admin/import/index.php?leftmenu=import', 'FormatedImport', 0, 'imports', '$user->rights->import->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4131, 'tools', '', 4130, '/admin/import/import.php?leftmenu=import', 'NewImport', 1, 'imports', '$user->rights->import->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4200, 'members', '', 13, '/adherents/index.php?leftmenu=members&mainmenu=members', 'Members', 0, 'members', '$user->rights->adherent->lire', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4201, 'members', '', 4200, '/adherents/fiche.php?action=create', 'NewMember', 1, 'members', '$user->rights->adherent->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4202, 'members', '', 4200, '/adherents/liste.php', 'List', 1, 'members', '$user->rights->adherent->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4203, 'members', '', 4200, '/adherents/liste.php?statut=-1', 'MenuMembersToValidate', 1, 'members', '$user->rights->adherent->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4204, 'members', '', 4200, '/adherents/liste.php?statut=1', 'MenuMembersValidated', 1, 'members', '$user->rights->adherent->lire', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4205, 'members', '', 4200, '/adherents/liste.php?statut=1&filter=outofdate', 'MenuMembersNotUpToDate', 1, 'members', '$user->rights->adherent->lire', '', 2, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4206, 'members', '', 4200, '/adherents/liste.php?statut=1&filter=uptodate', 'MenuMembersUpToDate', 1, 'members', '$user->rights->adherent->lire', '', 2, 5);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4207, 'members', '', 4200, '/adherents/liste.php?statut=0', 'MenuMembersResiliated', 1, 'members', '$user->rights->adherent->lire', '', 2, 6);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4300, 'members', '', 13, '/adherents/index.php?leftmenu=accountancy&mainmenu=members', 'Subscriptions', 0, 'compta', '$user->rights->adherent->cotisation->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4301, 'members', '', 4300, '/adherents/liste.php?statut=-1&leftmenu=accountancy&mainmenu=members', 'NewSubscription', 1, 'compta', '$user->rights->adherent->cotisation->creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4302, 'members', '', 4300, '/adherents/cotisations.php?leftmenu=accountancy', 'List', 1, 'compta', '$user->rights->adherent->cotisation->lire', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4400, 'members', '', 13, '/compta/bank/index.php?leftmenu=accountancy', 'Bank', 0, 'banks', '$user->rights->adherent->lire', '', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4500, 'members', '', 13, '/adherents/index.php?leftmenu=export&mainmenu=members', 'Exports', 0, 'members', '$user->rights->adherent->export', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4501, 'members', '$leftmenu=="export"', 4500, '/exports/index.php?leftmenu=export', 'Datas', 1, 'members', '$user->rights->adherent->export', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4502, 'members', '$leftmenu=="export"', 4500, '/adherents/htpasswd.php?leftmenu=export', 'Filehtpasswd', 1, 'members', '$user->rights->adherent->export', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4503, 'members', '$leftmenu=="export"', 4500, '/adherents/cartes/carte.php?leftmenu=export', 'MembersCards', 1, 'members', '$user->rights->adherent->export', '_new', 2, 2);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4504, 'members', '$leftmenu=="export"', 4500, '/adherents/cartes/etiquette.php?leftmenu=export', 'Etiquettes d''adhérents', 1, 'members', '$user->rights->adherent->export', '_new', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4600, 'members', '', 13, '/public/adherents/index.php?leftmenu=member_public', 'MemberPublicLinks', 0, 'members', '$user->rights->adherent->export', '', 2, 4);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4700, 'members', '', 13, '/adherents/index.php?leftmenu=setup&mainmenu=members', 'Setup', 0, 'members', '$user->rights->adherent->configurer', '', 2, 5);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4701, 'members', '', 4700, '/adherents/type.php?leftmenu=setup', 'MembersTypes', 1, 'members', '$user->rights->adherent->configurer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4702, 'members', '', 4700, '/adherents/options.php?leftmenu=setup', 'MembersAttributes', 1, 'members', '$user->rights->adherent->configurer', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4800, 'product', '', 3, '/product/droitpret/index.php?leftmenu=droitpret', 'Droit de prêt', 0, 'products', '$user->rights->droitpret->lire', '', 2, 5);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4801, 'product', '$leftmenu=="droitpret"', 4800, '/product/droitpret/index.php?leftmenu=droitpret', 'Générer rapport', 1, 'products', '$user->rights->droitpret->creer', '', 2, 1);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4900, 'suppliers', '', 4, '/categories/index.php?leftmenu=cat&type=1', 'Categories', 0, 'categories', '$user->rights->categorie>lire', '', 2, 3);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (4901, 'suppliers', '$leftmenu=="cat"', 4900, '/categories/fiche.php?action=create&type=1', 'NewCat', 1, 'categories', '$user->rights->categorie>creer', '', 2, 0);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (5000, 'commercial', '', 5, '/categories/index.php?leftmenu=cat&type=2', 'Categories', 0, 'commercial', '$user->rights->categorie>lire', '', 2, 9);
insert into `llx_menu` (`rowid`, `mainmenu`, `leftmenu`, `fk_menu`, `url`, `titre`, `level`, `langs`, `right`, `target`, `user`, `order`) values (5001, 'commercial', '$leftmenu=="cat"', 5000, '/categories/fiche.php?action=create&type=2', 'NewCat', 1, 'commercial', '$user->rights->categorie>creer', '', 2, 0);
update llx_menu set type='top' where level=-1;

-- 
-- Contenu de la table `llx_menu_constraint`
-- 
insert into `llx_menu_constraint` (`rowid`, `action`) values (1, '$user->admin');
insert into `llx_menu_constraint` (`rowid`, `action`) values (2, '$conf->societe->enabled && $user->rights->societe->lire');
insert into `llx_menu_constraint` (`rowid`, `action`) values (3, '$user->rights->societe->creer');
insert into `llx_menu_constraint` (`rowid`, `action`) values (4, 'is_dir("societe/groupe")');
insert into `llx_menu_constraint` (`rowid`, `action`) values (5, '$conf->societe->enabled && $conf->fournisseur->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (6, '$user->societe_id == 0');
insert into `llx_menu_constraint` (`rowid`, `action`) values (7, '$conf->propal->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (8, '$conf->commande->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (9, '$conf->expedition->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (10, '$conf->contrat->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (11, '$conf->fichinter->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (12, '$conf->societe->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (13, '$conf->facture->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (14, '! $conf->global->FACTURE_DISABLE_RECUR');
insert into `llx_menu_constraint` (`rowid`, `action`) values (15, '$conf->don->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (16, '$conf->deplacement->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (17, '$conf->tax->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (18, '($conf->compta->enabled || $conf->comptaexpert->enabled) && $conf->compta->tva && $user->societe_id == 0');
insert into `llx_menu_constraint` (`rowid`, `action`) values (19, '$conf->compta-enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (20, '$conf->prelevement->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (21, '$conf->banque->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (22, '$conf->compta->enabled || $conf->comptaexpert->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (23, '$conf->produit->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (24, '$conf->stock->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (25, '$conf->service->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (26, '$conf->categorie->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (27, '$conf->projet->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (28, '$conf->mailing->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (29, '$conf->bookmark->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (30, '$conf->export->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (31, '$conf->adherent->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (32, '($conf->societe->enabled && $user->rights->societe->lire) || ($conf->fournisseur->enabled && $user->rights->fournisseur->lire)');
insert into `llx_menu_constraint` (`rowid`, `action`) values (33, '$conf->produit->enabled || $conf->service->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (34, '$conf->fournisseur->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (35, '$conf->commercial->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (36, '$conf->compta->enabled || $conf->comptaexpert->enabled || $conf->banque->enabled\r\n        	|| $conf->commande->enabled || $conf->facture->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (37, '$conf->mailing->enabled || $conf->export->enabled || $conf->bookmark->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (38, '$conf->boutique->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (39, '$conf->oscommerce2->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (40, '$conf->webcal->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (41, '$conf->mantis->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (42, '(dolibarr_get_const($this->db,"PRODUIT_SPECIAL_LIVRE")) && (dolibarr_get_const($this->db,"PRODUCT_CANVAS_ABILITY"))');
insert into `llx_menu_constraint` (`rowid`, `action`) values (43, '!((dolibarr_get_const($this->db,"PRODUIT_SPECIAL_LIVRE")) && (dolibarr_get_const($this->db,"PRODUCT_CANVAS_ABILITY")))');
insert into `llx_menu_constraint` (`rowid`, `action`) values (44, '$conf->droitpret->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (45, '$conf->menudb->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (46, '$conf->energie->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (47, '$conf->telephonie->enabled');
insert into `llx_menu_constraint` (`rowid`, `action`) values (48, '($user->admin && function_exists("eaccelerator_info"))');
insert into `llx_menu_constraint` (`rowid`, `action`) values (49, '$conf->import->enabled');

-- 
-- Contenu de la table `llx_menu_const`
-- 
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (1, 100, 1, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (2, 200, 1, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (3, 300, 1, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (4, 304, 48, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (5, 501, 3, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (6, 502, 4, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (7, 504, 6, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (8, 503, 5, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (9, 504, 5, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (10, 505, 5, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (11, 500, 2, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (12, 1100, 7, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (13, 1200, 8, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (14, 1300, 9, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (15, 1400, 10, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (16, 1500, 11, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (17, 1600, 5, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (18, 1601, 6, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (19, 1603, 12, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (20, 1605, 6, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (21, 1604, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (22, 1605, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (23, 1606, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (24, 1607, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (25, 1701, 6, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (26, 1700, 12, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (27, 1705, 6, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (28, 1706, 14, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (29, 1704, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (30, 1705, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (31, 1706, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (32, 1708, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (33, 1709, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (34, 1710, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (35, 1711, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (36, 1712, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (37, 1713, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (38, 1714, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (39, 1800, 7, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (40, 1900, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (41, 1900, 8, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (42, 2000, 15, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (43, 2100, 16, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (44, 2200, 17, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (45, 2300, 18, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (46, 2400, 19, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (47, 2500, 20, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (48, 2300, 21, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (49, 2700, 22, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (50, 2800, 23, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (51, 2801, 6, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (52, 2803, 24, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (53, 2900, 25, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (54, 2901, 6, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (55, 3000, 7, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (56, 3100, 24, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (57, 3200, 26, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (58, 3201, 6, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (59, 3300, 5, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (60, 3301, 6, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (61, 3400, 13, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (62, 3401, 6, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (63, 3500, 8, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (64, 3600, 27, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (65, 3700, 27, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (66, 3800, 27, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (67, 3900, 28, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (68, 4000, 29, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (69, 4100, 30, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (70, 4130, 49, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (71, 4200, 31, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (72, 4300, 31, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (73, 4400, 31, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (74, 4500, 31, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (75, 4600, 31, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (76, 4700, 31, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (77, 4400, 21, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (78, 4501, 30, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (79, 2, 32, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (80, 3, 33, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (81, 4, 34, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (82, 5, 35, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (83, 6, 36, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (84, 7, 27, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (85, 8, 37, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (86, 9, 47, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (87, 10, 46, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (88, 11, 38, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (89, 12, 39, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (90, 13, 40, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (91, 14, 41, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (92, 15, 31, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (100, 1715, 13, 1);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (101, 1716, 13, 1);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (102, 1717, 13, 1);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (103, 2804, 42, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (104, 2805, 42, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (105, 2801, 43, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (106, 2802, 43, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (110, 4800, 44, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (111, 4900, 26, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (112, 4901, 6, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (113, 5000, 26, 2);
insert into `llx_menu_const` (`rowid`, `fk_menu`, `fk_constraint`, `user`) values (114, 5001, 6, 2);

--
-- Eco-Taxes
--

-- France (Organisme ERP)
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (1, 'ER-A-A', 'Matériels électriques < 0,2kg', 0.01000000, 'ERP', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (2, 'ER-A-B', 'Matériels électriques >= 0,2 kg et < 0,5 kg', 0.03000000, 'ERP', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (3, 'ER-A-C', 'Matériels électriques >= 0,5 kg et < 1 kg', 0.04000000, 'ERP', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (4, 'ER-A-D', 'Matériels électriques >= 1 kg et < 2 kg', 0.13000000, 'ERP', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (5, 'ER-A-E', 'Matériels électriques >= 2 kg et < 4kg', 0.21000000, 'ERP', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (6, 'ER-A-F', 'Matériels électriques >= 4 kg et < 8 kg', 0.42000000, 'ERP', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (7, 'ER-A-G', 'Matériels électriques >= 8 kg et < 15 kg', 0.84000000, 'ERP', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (8, 'ER-A-H', 'Matériels électriques >= 15 kg et < 20 kg', 1.25000000, 'ERP', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (9, 'ER-A-I', 'Matériels électriques >= 20 kg et < 30 kg', 1.88000000, 'ERP', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (10, 'ER-A-J', 'Matériels électriques >= 30 kg', 3.34000000, 'ERP', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (11, 'ER-M-1', 'TV, Moniteurs < 9kg', 0.84000000, 'ERP', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (12, 'ER-M-2', 'TV, Moniteurs >= 9kg et < 15kg', 1.67000000, 'ERP', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (13, 'ER-M-3', 'TV, Moniteurs >= 15kg et < 30kg', 3.34000000, 'ERP', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (14, 'ER-M-4', 'TV, Moniteurs >= 30 kg', 6.69000000, 'ERP', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (15, 'EC-A-A', 'Matériels électriques  0,2 kg max', 0.00840000, 'Ecologic', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (16, 'EC-A-B', 'Matériels électriques 0,21 kg min - 0,50 kg max', 0.02500000, 'Ecologic', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (17, 'EC-A-C', 'Matériels électriques  0,51 kg min - 1 kg max', 0.04000000, 'Ecologic', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (18, 'EC-A-D', 'Matériels électriques  1,01 kg min - 2,5 kg max', 0.13000000, 'Ecologic', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (19, 'EC-A-E', 'Matériels électriques  2,51 kg min - 4 kg max', 0.21000000, 'Ecologic', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (20, 'EC-A-F', 'Matériels électriques 4,01 kg min - 8 kg max', 0.42000000, 'Ecologic', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (21, 'EC-A-G', 'Matériels électriques  8,01 kg min - 12 kg max', 0.63000000, 'Ecologic', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (22, 'EC-A-H', 'Matériels électriques 12,01 kg min - 20 kg max', 1.05000000, 'Ecologic', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (23, 'EC-A-I', 'Matériels électriques  20,01 kg min', 1.88000000, 'Ecologic', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (24, 'EC-M-1', 'TV, Moniteurs 9 kg max', 0.84000000, 'Ecologic', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (25, 'EC-M-2', 'TV, Moniteurs 9,01 kg min - 18 kg max', 1.67000000, 'Ecologic', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (26, 'EC-M-3', 'TV, Moniteurs 18,01 kg min - 36 kg max', 3.34000000, 'Ecologic', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (27, 'EC-M-4', 'TV, Moniteurs 36,01 kg min', 6.69000000, 'Ecologic', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (28, 'ES-M-1', 'TV, Moniteurs <= 20 pouces', 0.84000000, 'Eco-systèmes', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (29, 'ES-M-2', 'TV, Moniteurs > 20 pouces et <= 32 pouces', 3.34000000, 'Eco-systèmes', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (30, 'ES-M-3', 'TV, Moniteurs > 32 pouces et autres grands écrans', 6.69000000, 'Eco-systèmes', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (31, 'ES-A-A', 'Ordinateur fixe, Audio home systems (HIFI), éléments hifi séparés…', 0.84000000, 'Eco-systèmes', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (32, 'ES-A-B', 'Ordinateur portable, CD-RCR, VCR, lecteurs et enregistreurs DVD …  Instruments de musique et caisses de résonance, haut parleurs...', 0.25000000, 'Eco-systèmes', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (33, 'ES-A-C', 'Imprimante, photocopieur, télécopieur,…', 0.42000000, 'Eco-systèmes', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (34, 'ES-A-D', 'Accessoires, clavier, souris, PDA, imprimante photo, appareil photo, gps, téléphone, répondeur, téléphone sans fil, modem,...   Télécommande, casque, caméscope, baladeur mp3, radio portable, radio K7 et CD portable, set top box, radio réveil …', 0.08400000, 'Eco-systèmes', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (35, 'ES-A-E', 'GSM', 0.00840000, 'Eco-systèmes', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (36, 'ES-A-F', 'Jouets et équipements de loisirs et de sports < 0,5 kg', 0.04200000, 'Eco-systèmes', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (37, 'ES-A-G', 'Jouets et équipements de loisirs et de sports > 0,5 kg', 0.17000000, 'Eco-systèmes', 1, 1);
INSERT INTO `llx_c_ecotaxe` (`rowid`, `code`, `libelle`, `price`, `organization`, `fk_pays`, `active`) VALUES (38, 'ES-A-H', 'Jouets et équipements de loisirs et de sports > 10 kg', 1.25000000, 'Eco-systèmes', 1, 1);

--
-- Codes barres
--
INSERT INTO llx_c_barcode (rowid, code, libelle, coder, example) VALUES (1, 'EAN8', 'EAN8', 0, '1234567');
INSERT INTO llx_c_barcode (rowid, code, libelle, coder, example) VALUES (2, 'EAN13', 'EAN13', 0, '123456789012');
INSERT INTO llx_c_barcode (rowid, code, libelle, coder, example) VALUES (3, 'UPC', 'UPC', 0, '123456789012');
INSERT INTO llx_c_barcode (rowid, code, libelle, coder, example) VALUES (4, 'ISBN', 'ISBN', 0, '123456789');
INSERT INTO llx_c_barcode (rowid, code, libelle, coder, example) VALUES (5, 'C39', 'Code 39', 0, '1234567890');
INSERT INTO llx_c_barcode (rowid, code, libelle, coder, example) VALUES (6, 'C128', 'Code 128', 0, 'ABCD1234567890');

--
-- Formats de papier
--
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (1, '4A0', 'Format 4A0', '1682', '2378', 'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (2, '2A0', 'Format 2A0', '1189', '1682', 'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (3, 'A0', 'Format A0', '840', '1189', 'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (4, 'A1', 'Format A1', '594', '840', 'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (5, 'A2', 'Format A2', '420', '594', 'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (6, 'A3', 'Format A3', '297', '420', 'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (7, 'A4', 'Format A4', '210', '297', 'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (8, 'A5', 'Format A5', '148', '210', 'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (9, 'A6', 'Format A6', '105', '148', 'mm', 1);