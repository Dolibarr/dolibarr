--
-- Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
--
-- $Id$
-- $Source$
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
--
-- Valeurs pour les bases de langues francaises
--

insert into llx_sqltables (name, loaded) values ('llx_album',0);

--
-- Boites
--
insert into llx_boxes_def (name, file) values ('Factures','box_factures.php');
insert into llx_boxes_def (name, file) values ('Factures impayées','box_factures_imp.php');
insert into llx_boxes_def (name, file) values ('Propales','box_propales.php');

--
-- Constantes de configuration
--
insert into llx_const(name, value, type) values ('DONS_FORM','fsfe.fr.php','chaine');
insert into llx_const(name, value, type) values ('DB_NAME_OSC','catalog','chaine');
insert into llx_const(name, value, type) values ('OSC_LANGUAGE_ID','1','chaine');

insert into llx_const(name, value, type, note) values ('MAIN_SEARCHFORM_SOCIETE','1','yesno','Affichage du formulaire de recherche des sociétés dans la barre de gauche');
insert into llx_const(name, value, type, note) values ('MAIN_SEARCHFORM_CONTACT','1','yesno','Affichage du formulaire de recherche des contacts dans la barre de gauche');

insert into llx_const(name, value, type, note) values ('COMPTA_ONLINE_PAYMENT_BPLC','1','yesno','Système de gestion de la banque populaire de Lorraine');

INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_THEME','dolibarr','chaine','theme principal');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_TITLE','Adherents test J1B','chaine','Titre des pages');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_MAIL_RESIL','Votre adhesion sur %SERVEUR% vient d\'etre resilie.\r\nNous esperons vous revoir tres bientot','texte','Mail de Resiliation');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_MAIL_VALID','MAIN\r\nVotre adhesion vient d\'etre validee. \r\nVoici le rappel de vos coordonnees (toute information erronee entrainera la non validation de votre inscription) :\r\n\r\n%INFO%\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante : \r\n%SERVEUR%public/adherents/','texte','Mail de validation');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_MAIL_EDIT','Voici le rappel des coordonnees que vous avez modifiees (toute information erronee entrainera la non validation de votre inscription) :\r\n\r\n%INFO%\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante :\r\n%SERVEUR%public/adherents/','texte','Mail d\'edition');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_MAIL_NEW','Merci de votre inscription. Votre adhesion devrait etre rapidement validee.\r\nVoici le rappel des coordonnees que vous avez rentrees (toute information erronee entrainera la non validation de votre inscription) :\r\n\r\n%INFO%\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l\'adresse suivante :\r\n%SERVEUR%public/adherents/','texte','Mail de nouvel inscription');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_MAIL_VALID_SUBJECT','Votre adhésion a ete validée sur %SERVEUR%','chaine','sujet du mail de validation');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_MAIL_RESIL_SUBJECT','Resiliation de votre adhesion sur %SERVEUR%','chaine','sujet du mail de resiliation');
INSERT INTO llx_const (name, value, type, note) VALUES ('SIZE_LISTE_LIMIT','50','chaine','Taille des listes');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_MAIL_NEW_SUBJECT','Bienvenue sur %SERVEUR%','chaine','Sujet du mail de nouvelle adhesion');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_MAIL_EDIT_SUBJECT','Votre fiche a ete editee sur %SERVEUR%','chaine','Sujet du mail d\'edition');

INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_USE_MAILMAN','1','yesno','Utilisation de Mailman');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_MAILMAN_UNSUB_URL','http://lists.ipsyn.net/cgi-bin/mailman/handle_opts/%LISTE%/%EMAIL%?upw=%PASS%&unsub=Unsubscribe','chaine','Url de desinscription aux listes mailman');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_MAILMAN_URL','http://lists.ipsyn.net/cgi-bin/mailman/subscribe/%LISTE%/?email=%EMAIL%&pw=%PASS%&pw-conf=%PASS%','chaine','url pour les inscriptions mailman');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_MAILMAN_LISTS','test-test,test-test2','chaine','Listes auxquelles inscrire les nouveaux adherents');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_DEBUG','1','yesno','Debug ..');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_USE_GLASNOST','0','yesno','utilisation de glasnost ?');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_GLASNOST_SERVEUR','glasnost.j1b.org','chaine','serveur glasnost');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_GLASNOST_USER','user','chaine','Administrateur glasnost');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_GLASNOST_PASS','password','chaine','password de l\'administrateur');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_USE_GLASNOST_AUTO','1','yesno','inscription automatique a glasnost ?');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_USE_SPIP','1','yesno','Utilisation de SPIP ?');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_USE_SPIP_AUTO','1','yesno','Utilisation de SPIP automatiquement');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_SPIP_USER','user','chaine','user spip');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_SPIP_PASS','pass','chaine','Pass de connection');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_SPIP_SERVEUR','localhost','chaine','serveur spip');
INSERT INTO llx_const (name, value, type, note) VALUES ('MAIN_SPIP_DB','spip','chaine','db spip');

--
-- Constantes 
--

delete from c_chargesociales;
insert into c_chargesociales (id,libelle,deductible) values ( 1, 'Allocations familiales',1);
insert into c_chargesociales (id,libelle,deductible) values ( 2, 'GSG Deductible',1);
insert into c_chargesociales (id,libelle,deductible) values ( 3, 'GSG/CRDS NON Deductible',0);


delete from c_actioncomm;
insert into c_actioncomm (id,libelle) values ( 0, '-');
insert into c_actioncomm (id,libelle) values ( 1, 'Appel Téléphonique');
insert into c_actioncomm (id,libelle) values ( 2, 'Envoi Fax');
insert into c_actioncomm (id,libelle) values ( 3, 'Envoi propal par mail');
insert into c_actioncomm (id,libelle) values ( 4, 'Envoi d\'un email'); 
insert into c_actioncomm (id,libelle) values ( 5, 'Rendez-vous'); 
insert into c_actioncomm (id,libelle) values ( 9, 'Envoi Facture');
insert into c_actioncomm (id,libelle) values (10, 'Relance effectuée');
insert into c_actioncomm (id,libelle) values (11, 'Clôture');

delete from c_stcomm;
insert into c_stcomm (id,libelle) values (-1, 'NE PAS CONTACTER');
insert into c_stcomm (id,libelle) values ( 0, 'Jamais contacté');
insert into c_stcomm (id,libelle) values ( 1, 'A contacter');
insert into c_stcomm (id,libelle) values ( 2, 'Contact en cours');
insert into c_stcomm (id,libelle) values ( 3, 'Contactée');

--
-- Types d'entreprise
--
delete from c_typent;
insert into c_typent (id,libelle) values (  0, 'Indifférent');
insert into c_typent (id,libelle) values (  1, 'Start-up');
insert into c_typent (id,libelle) values (  2, 'Grand groupe');
insert into c_typent (id,libelle) values (  3, 'PME/PMI');
insert into c_typent (id,libelle) values (  4, 'Administration');
insert into c_typent (id,libelle) values (100, 'Autres');

--
-- Pays
--
delete from c_pays;
insert into c_pays (id,libelle,code) values (0, 'France',          'FR');
insert into c_pays (id,libelle,code) values (2, 'Belgique',        'BE');
insert into c_pays (id,libelle,code) values (3, 'Italie',          'IT');
insert into c_pays (id,libelle,code) values (4, 'Espagne',         'ES');
insert into c_pays (id,libelle,code) values (5, 'Allemagne',       'DE');
insert into c_pays (id,libelle,code) values (6, 'Suisse',          'CH');
insert into c_pays (id,libelle,code) values (7, 'Royaume uni',     'GB');
insert into c_pays (id,libelle,code) values (8, 'Irlande',         'IE');
insert into c_pays (id,libelle,code) values (9, 'Chine',           'CN');
insert into c_pays (id,libelle,code) values (10, 'Tunisie',        'TN');
insert into c_pays (id,libelle,code) values (11, 'Etats Unis',     'US');
insert into c_pays (id,libelle,code) values (12, 'Maroc',          'MA');
insert into c_pays (id,libelle,code) values (13, 'Algérie',        'DZ');
insert into c_pays (id,libelle,code) values (14, 'Canada',         'CA');
insert into c_pays (id,libelle,code) values (15, 'Togo',           'TG');
insert into c_pays (id,libelle,code) values (16, 'Gabon',          'GA');
insert into c_pays (id,libelle,code) values (17, 'Pays Bas',       'NL');
insert into c_pays (id,libelle,code) values (18, 'Hongrie',        'HU');
insert into c_pays (id,libelle,code) values (19, 'Russie',         'RU');
insert into c_pays (id,libelle,code) values (20, 'Suède',          'SE');
insert into c_pays (id,libelle,code) values (21, 'Côte d\'Ivoire', 'CI');
insert into c_pays (id,libelle,code) values (23, 'Sénégal',        'SN');
insert into c_pays (id,libelle,code) values (24, 'Argentine',      'AR');
insert into c_pays (id,libelle,code) values (25, 'Cameroun',       'CM');

--
-- Effectif des sociétés
--
delete from c_effectif;
insert into c_effectif (id,libelle) values (0,  'Non spécifié');
insert into c_effectif (id,libelle) values (1,  '1 - 5');
insert into c_effectif (id,libelle) values (2,  '6 - 10');
insert into c_effectif (id,libelle) values (3,  '11 - 50');
insert into c_effectif (id,libelle) values (4,  '51 - 100');
insert into c_effectif (id,libelle) values (5,  '100 - 500');
insert into c_effectif (id,libelle) values (6,  '> 500');

delete from c_paiement;
insert into c_paiement (id,libelle,type) values (0, '-', 3);
insert into c_paiement (id,libelle,type) values (1, 'TIP', 1);
insert into c_paiement (id,libelle,type) values (2, 'Virement', 2);
insert into c_paiement (id,libelle,type) values (3, 'Prélèvement', 1);
insert into c_paiement (id,libelle,type) values (4, 'Liquide', 0);
insert into c_paiement (id,libelle,type) values (5, 'Paiement en ligne', 0);
insert into c_paiement (id,libelle,type) values (6, 'CB', 1);
insert into c_paiement (id,libelle,type) values (7, 'Chèque', 2);

delete from c_propalst;
insert into c_propalst (id,label) values (0, 'Brouillon');
insert into c_propalst (id,label) values (1, 'Ouverte');
insert into c_propalst (id,label) values (2, 'Signée');
insert into c_propalst (id,label) values (3, 'Non Signée');
insert into c_propalst (id,label) values (4, 'Facturée');


--
-- Utilisateur
--
--insert into llx_user (name,firstname,code,login,pass,module_comm,module_compta,admin,webcal_login)
--values ('Quiedeville','Rodolphe','RQ','rodo','CRnN0Tam/s7z.',1,1,1,'rodo');
