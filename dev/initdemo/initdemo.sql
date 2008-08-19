-- ===========================================================================
-- Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2006      Laurent Destailleur  <eldy@users.sourceforge.net>
--
-- This program is free software. you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation. either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY. without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- $Id$
--
-- Valeurs de test pour les developpements
-- Ne pas hésiter a compléter ce fichier avec de nouvelles valeurs, plus on a
-- de données, mieux on peut tester l'appli.
-- ===========================================================================
-- ;

delete from llx_bank_account;
delete from llx_chargesociales;
delete from llx_contratdet;
delete from llx_contrat;
delete from llx_fichinter;
delete from llx_actioncomm;
delete from llx_propal;
delete from llx_propaldet;
delete from llx_paiement_facture;
delete from llx_paiement;
delete from llx_facturedet;
delete from llx_facture;
delete from llx_compta_account;
delete from llx_facture_fourn_det;
delete from llx_facture_fourn;
delete from llx_socpeople;
delete from llx_commande_fournisseur;
delete from llx_commande;
delete from llx_societe_remise_except;
delete from llx_societe;
delete from llx_boxes;
delete from llx_user_rights;
delete from llx_user;
delete from llx_tva;
delete from llx_energie_compteur;
delete from llx_energie_compteur_releve;
delete from llx_energie_compteur_groupe;
delete from llx_energie_groupe;
delete from llx_categorie_product;
delete from llx_product;
delete from llx_product_fournisseur_price;

DELETE FROM llx_const WHERE name = 'MAIN_NOT_INSTALLED';

insert into llx_tva (datep, datev, amount) values ('2001-11-11','2001-10-01', 1960.00);
insert into llx_tva (datep, datev, amount) values ('2001-04-11','2001-01-01', 2000.00);


--
-- Societe les fournisseurs sont sur les numéros pairs
--


insert into llx_societe (rowid,nom,address,datec,cp,ville,tel,fax, client, prefix_comm)
values (1,'Cumulo','3 place de la République',now(),'56610','Arradon','01 40 15 03 18','01 40 15 06 18',1,'CU');

insert into llx_societe (rowid,nom,address,datec,cp,ville,tel,fax, client, prefix_comm, fournisseur, url, fk_forme_juridique)
values (2,'Bolix SA','13 rue Pierre Mendès France',now(),'56350','Allaire','01 40 15 03 18','01 40 15 06 18',1,'LO',1,'www.dolibarr.com',54);

insert into llx_societe (rowid,nom,address,cp,ville,tel,fax,client, prefix_comm)
values (10,'Doli INC.','Rue du Port','29300','Arzano','01 55 55 03 18','01 55 55 55 55',1,'DO');

insert into llx_societe (rowid,nom,address,cp,ville,tel,fax,client, prefix_comm,url, fournisseur)
values (3,'Foo SARL','3bis Avenue de la Liberté','22300','Ploubezre','01 55 55 03 18','01 55 55 55 55',1,'FOO','www.gnu.org', 1);

insert into llx_societe (rowid,nom,address,datec,cp,ville,tel,fax, client, prefix_comm)
values (4,'Talphinfo','Place Dolores Ibarruri',now(),'29400','Bodilis','01 40 15 03 18','01 40 15 06 18',1,'AP');

insert into llx_societe (rowid,nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values (20,'Bouleau','22800','Le Foeil','01 55 55 03 18','01 55 55 55 55',1,'BTP');

insert into llx_societe (nom,datec,cp,ville,tel,fax, client, prefix_comm)
values ('Valphanix',now(),'29820','Bohars','01 40 15 03 18','01 40 15 06 18',2,'AL');

insert into llx_societe (rowid,nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values (101,'Cerisier','22290','Goudelin','01 55 55 03 18','01 55 55 55 55',1,'CER');

insert into llx_societe (nom,cp,ville,tel,fax,client,url)
values ('Turin','29890','Brignogan-Plage','01 55 55 03 18','01 55 55 55 55',1,'http://www.ot-brignogan-plage.fr/');

insert into llx_societe (rowid,nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values (100,'Chêne','22330','Le Gouray','01 55 55 03 18','01 55 55 55 55',1,'DEL');

insert into llx_societe (rowid,nom,cp,ville,tel,fax,client)
values (5,'Yratin SA','29660','Carantec','01 55 55 03 18','01 55 55 55 55',1);

insert into llx_societe (rowid,nom,cp,ville,tel,fax,client)
values (6,'Raggos SARL','29233','Cléder','01 55 55 03 18','01 55 55 55 55',1);

insert into llx_societe (rowid,nom,cp,ville,tel,fax,client)
values (7,'Pruitosa','29870','Coat-Méal','01 55 55 03 18','01 55 55 55 55',2);

insert into llx_societe (rowid,nom,cp,ville,tel,fax,client)
values (8,'Stratus','29120','Combrit','01 55 55 03 18','01 55 55 55 55',2);

insert into llx_societe (rowid,nom,cp,ville,tel,fax,client,address)
values (9,'Nimbus','29490','Guipavas','01 55 55 03 18','01 55 55 55 55',2,'15 rue des petites écuries');

insert into llx_societe (nom,cp,ville,tel,fax,client)
values ('Iono','22110','Rostrenen','01 55 55 03 18','01 55 55 55 55',2);

insert into llx_societe (nom,datec,cp,ville,tel,fax, client, prefix_comm,address)
values ('Bolan',now(),'29820','Bohars','01 40 15 03 18','01 40 15 06 18',2,'CAL','104 Avenue de la Marne');

insert into llx_societe (nom,datec,cp,ville,tel,fax, client, prefix_comm,address)
values ('Travail Temporaire Boharssais',now(),'29820','Bohars','01 40 15 03 18','01 40 15 06 18',2,'TTBOH','125 Rue des moineaux');
--
-- Utilisateurs
--
replace into llx_user (rowid, name,firstname,login,pass,webcal_login,datec,admin)
values (1,'demo','demo','demo','demo','demo',sysdate(),0);

replace into llx_user (rowid,name,firstname,login,pass,webcal_login,datec)
values (2,'demo1','demo1','demo1','demo','demo1',sysdate());

replace into llx_user (rowid,name,firstname,login,pass,webcal_login,datec)
values (3,'demo2','demo2','demo2','demo','demo2',sysdate());

--
-- Facture fournisseurs
--

insert into llx_facture_fourn (facnumber, fk_soc, datec, datef, paye, amount, remise, tva, total, fk_statut, fk_user_author, fk_user_valid, note) 
values ('LOL-509',1,'2001-05-09','2001-05-09',1,1000,0,196,1196,1,1,1,'');
insert into llx_facture_fourn (facnumber, fk_soc, datec, datef, paye, amount, remise, tva, total, fk_statut, fk_user_author, fk_user_valid, note) 
values ('LOL-510',1,'2001-09-09','2001-09-09',1,100,0,19.6,119.6,1,1,1,'');

insert into llx_facture_fourn (facnumber, fk_soc, datec, datef, paye, amount, remise, tva, total, fk_statut, fk_user_author, fk_user_valid, note) 
values ('02-1-YHGT',2,now(),'2002-01-01',1,100,0,19.6,119.6,1,NULL,NULL,'');

insert into llx_facture_fourn (facnumber, fk_soc, datec, datef, paye, amount, remise, tva, total, fk_statut, fk_user_author, fk_user_valid, note) 
values ('02-5-YHGT',2,now(),'2002-05-01',1,1000,0,196,1196,1,NULL,NULL,'');

insert into llx_facture_fourn (facnumber, fk_soc, datec, datef, paye, amount, remise, tva, total, fk_statut, fk_user_author, fk_user_valid, note) 
values ('02-10-YHGT',2,now(),'2002-10-01',1,1000,0,196,1196,1,NULL,NULL,'');
insert into llx_facture_fourn (facnumber, fk_soc, datec, datef, paye, amount, remise, tva, total, fk_statut, fk_user_author, fk_user_valid, note) 
values ('02-11-YHGT',2,now(),'2002-11-01',1,1000,0,196,1196,1,NULL,NULL,'');
insert into llx_facture_fourn (facnumber, fk_soc, datec, datef, paye, amount, remise, tva, total, fk_statut, fk_user_author, fk_user_valid, note) 
values ('02-12-YHGT',2,now(),'2002-12-01',1,1000,0,196,1196,1,NULL,NULL,'');

REPLACE INTO llx_const (name, value, type, visible) VALUES ('MAIN_THEME',  'eldy_backoffice','chaine',0);
REPLACE INTO llx_const (name, value, type, visible) VALUES ('MAIN_MENUFRONT_BARRELEFT',  'eldy_frontoffice','chaine',0);
REPLACE INTO llx_const (name, value, type, visible) VALUES ('MAIN_MENUFRONT_BARRETOP',  'eldy_frontoffice','chaine',0);
REPLACE INTO llx_const (name, value, type, visible) VALUES ('MAIN_MENU_BARRELEFT',  'eldy_backoffice','chaine',0);
REPLACE INTO llx_const (name, value, type, visible) VALUES ('MAIN_MENU_BARRETOP',  'eldy_backoffice','chaine',0);

REPLACE INTO llx_const (name, value, type, visible) VALUES ('MAIN_INFO_SOCIETE_NOM','Barridol','chaine',0);
REPLACE INTO llx_const (name, value, type, visible) VALUES ('MAIN_INFO_SOCIETE_ADRESSE','10 road street','chaine',0);
REPLACE INTO llx_const (name, value, type, visible) VALUES ('MAIN_INFO_SOCIETE_CP','75000','chaine',0);
REPLACE INTO llx_const (name, value, type, visible) VALUES ('MAIN_INFO_SOCIETE_VILLE','BigTown','chaine',0);
REPLACE INTO llx_const (name, value, type, visible) VALUES ('MAIN_INFO_SOCIETE_PAYS','1','chaine',0);

REPLACE INTO llx_const (name, value, type, visible) VALUES ('MAIN_INFO_SOCIETE_TEL','01 02 03 04 05','chaine',0);
REPLACE INTO llx_const (name, value, type, visible) VALUES ('MAIN_INFO_SOCIETE_FAX','01 02 03 04 06','chaine',0);
REPLACE INTO llx_const (name, value, type, visible) VALUES ('MAIN_INFO_SOCIETE_WEB','www.dolibarr.org','chaine',0);
REPLACE INTO llx_const (name, value, type, visible) VALUES ('MAIN_INFO_SOCIETE_MAIL','mycompany@mycompany.com','chaine',0);

REPLACE INTO llx_const (name, value, type, visible) VALUES ('MAIN_INFO_TVAINTRA','12345679012345','chaine',0);
REPLACE INTO llx_const (name, value, type, visible) VALUES ('MAIN_INFO_CAPITAL','15000','chaine',0);
REPLACE INTO llx_const (name, value, type, visible) VALUES ('MAIN_INFO_SIREN','123456789','chaine',0);
REPLACE INTO llx_const (name, value, type, visible) VALUES ('MAIN_INFO_SIRET','123456789001','chaine',0);
REPLACE INTO llx_const (name, value, type, visible) VALUES ('MAIN_INFO_APE','721Z','chaine',0);







INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,11);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,12);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,13);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,14);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,15);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,16);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,17);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,18);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,19);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,21);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,22);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,23);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,24);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,25);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,26);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,27);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,28);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,29);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,31);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,32);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,33);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,34);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,35);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,36);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,37);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,38);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,39);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,41);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,42);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,43);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,44);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,45);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,46);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,47);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,48);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,49);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,61);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,62);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,63);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,64);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,65);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,66);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,67);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,68);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,69);

INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,71);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,72);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,73);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,74);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,75);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,76);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,77);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,78);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,79);

INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,81);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,82);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,83);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,84);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,85);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,86);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,87);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,88);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,89);

INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,91);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,92);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,93);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,94);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,95);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,96);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,97);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,98);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,99);

INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,101);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,102);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,103);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,104);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,105);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,106);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,107);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,108);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,109);

INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,111);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,112);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,113);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,114);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,115);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,116);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,117);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,118);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,119);

INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,121);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,122);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,123);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,124);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,125);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,126);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,127);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,128);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,129);

INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,161);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,162);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,163);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,164);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,165);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,166);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,167);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,168);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,169);

INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,221);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,222);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,223);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,224);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,225);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,226);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,227);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,228);
INSERT INTO llx_user_rights (fk_user, fk_id) VALUES (1,229);


--
-- Boites
--


--
-- Contact
--

insert into llx_socpeople (rowid,fk_soc, name, firstname, phone,fax,email,poste,fk_user_creat)
values (10,1,'Maréchal','Ferdinand','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net','Administrateur système',0);
insert into llx_socpeople (rowid,fk_soc, name, firstname, phone,fax,email,fk_user_creat)
values (11,5,'Pejat','Jean-Marie','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net',0);

insert into llx_socpeople (rowid,fk_soc, name, firstname, phone,fax,email,poste,fk_user_creat)
values (12,1,'Poulossière','Paul','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net','Directeur technique',0);
insert into llx_socpeople (rowid,fk_soc, name, firstname, phone,fax,email,fk_user_creat)
values (13,6,'Myriam','Isabelle','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net',0);

insert into llx_socpeople (rowid,fk_soc, name, firstname, phone,fax,email,fk_user_creat)
values (20,2,'Corin','Arnaud','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net',0);
insert into llx_socpeople (rowid,fk_soc, name, firstname, phone,fax,email,fk_user_creat)
values (30,3,'Philippine','Sagan','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net',0);
insert into llx_socpeople (rowid,fk_soc, name, firstname, phone,fax,email,fk_user_creat)
values (31,3,'Marie','Jeanne','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net',0);
insert into llx_socpeople (rowid,fk_soc, name, firstname, phone,fax,email,fk_user_creat)
values (41,4,'Alix','Hopper','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net',0);
insert into llx_socpeople (rowid,fk_soc, name, firstname, phone,fax,email,fk_user_creat)
values (14,7,'Victoire','Renoir','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net',0);
insert into llx_socpeople (rowid,fk_soc, name, firstname, phone,fax,email,fk_user_creat)
values (15,7,'Baudelaire','Matthias','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net',0);
insert into llx_socpeople (rowid,fk_soc, name, firstname, phone,fax,email,fk_user_creat)
values (16,8,'Hugo','Benjamin','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net',0);
insert into llx_socpeople (rowid,fk_soc, name, firstname, phone,fax,email,fk_user_creat)
values (17,9,'Rembrandt','Stéphanie','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net',0);
insert into llx_socpeople (rowid,fk_soc, name, firstname, phone,fax,email,fk_user_creat)
values (18,10,'Picasso','Myriam','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net',0);
insert into llx_socpeople (rowid,fk_soc, name, firstname, phone,fax,email,fk_user_creat)
values (19,1,'Beethoven','John','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net',0);
insert into llx_socpeople (rowid,fk_soc, name, firstname, phone,fax,email,fk_user_creat)
values (22,6,'Dumas','Elisabeth','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net',0);
insert into llx_socpeople (rowid,fk_soc, name, firstname, phone,fax,email,fk_user_creat)
values (21,10,'','Joséphine','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net',0);
--
--
-- Produits
--
--
insert into llx_product (ref, label, description, price, tva_tx)
values ('RJ451MR','Câble Réseaux RJ45 1m rouge','Câble Réseaux RJ45 1m rouge',10,19.6);

insert into llx_product (ref, label, description, price, tva_tx)
values ('RJ454M','Câble Réseaux RJ45 4m','Câble Réseaux RJ45 4m\n couleur suivant stock',19.5,19.6);

insert into llx_product (ref, label, description, price, tva_tx)
values ('RJ452M','Câble Réseaux RJ45 2m','Câble Réseaux RJ45 2m',10,19.6);

insert into llx_product (ref, label, description, price, tva_tx)
values ('RJ458M','Câble Réseaux RJ45 8m','Câble Réseaux RJ45 8m',10,19.6);

insert into llx_product (ref, label, description, price, tva_tx)
values ('RJ4515M','Câble Réseaux RJ45 15m','Câble Réseaux RJ45 15m',10,19.6);

insert into llx_product (ref, label, description, price, tva_tx, fk_product_type, duration)
values ('HEB12MS','Hébergement serveur 12 mois','Hébergement serveur 12 mois',2400,19.6,1,'12m');

insert into llx_product (ref, label, description, price, tva_tx, fk_product_type, duration)
values ('HEB03MS','Hébergement serveur 3 mois','Hébergement serveur 3 mois',600,19.6,1,'3m');

insert into llx_product (ref, label, description, price, tva_tx, fk_product_type, duration)
values ('HEB06MS','Hébergement serveur 6 mois','Hébergement serveur 6 mois',1200,19.6,1,'6m');

insert into llx_product (ref, label, description, price, tva_tx)
values ('SW8','Switch 8 ports 100Mbits','Switch 8 ports 100Mbits',1000,19.6);

insert into llx_product (ref, label, description, price, tva_tx)
values ('SER1U','Serveur 1U Serie 3W','Serveur avec 1G de RAM et 2 processeurs',9750,19.6);

insert into llx_product (ref, label, description, price, tva_tx)
values ('HUB8-10','Hub 8 ports 10Mbits','Hub 8 ports',750,19.6);

insert into llx_product (ref, label, description, price, tva_tx)
values ('PB-16','Pan. Brass. 16','Panneau de brassage extensible, incluant 1 barre de 16 prises',650,19.6);

insert into llx_product (ref, label, description, price, tva_tx)
values ('PB-32','Pan. Brass. 32','Panneau de brassage extensible, incluant 2 barres de 16 prises',1200,19.6);

insert into llx_product (ref, label, description, price, tva_tx)
values ('HB-USB1','Hub Usb 4 ports','Hub USB 4 ports avec bloc d\'alimentation indépendant',31,19.6);

--
-- Liens produits fournisseurs
-- 
insert into llx_product_fournisseur (datec, fk_product,fk_soc,ref_fourn,fk_user_author)
values (now(),1,2,'2313487',1);
insert into llx_product_fournisseur (datec, fk_product,fk_soc,ref_fourn,fk_user_author)
values (now(),2,2,'2313409',1);
insert into llx_product_fournisseur (datec, fk_product,fk_soc,ref_fourn,fk_user_author)
values (now(),3,2,'2323134',1);
insert into llx_product_fournisseur (datec, fk_product,fk_soc,ref_fourn,fk_user_author)
values (now(),3,4,'2313784',1);
--
-- Fichinter
--
--

insert into llx_fichinter (fk_soc, ref, datec, date_valid, datei, fk_user_author, fk_user_valid, fk_statut, duree, note)
values (1, 'FI-LP-1','2001-12-05','2001-12-05','2001-12-05',1,1,1,4,'Mise à jour de la doc');
--
-- Actions commerciales
--

insert into llx_actioncomm (datea, fk_action,fk_soc,fk_user_author,fk_user_action,fk_contact) 
values ('2002-04-06',1,1,1,1,10);
insert into llx_actioncomm (datea, fk_action,fk_soc,fk_user_author,fk_user_action,fk_contact) 
values ('2002-04-05',2,1,1,1,12);
insert into llx_actioncomm (datea, fk_action,fk_soc,fk_user_author,fk_user_action,fk_contact) 
values ('2002-04-05',1,1,1,2,10);
insert into llx_actioncomm (datea, fk_action,fk_soc,fk_user_author,fk_user_action,fk_contact) 
values ('2002-04-02',3,1,1,1,13);
insert into llx_actioncomm (datea, fk_action,fk_soc,fk_user_author,fk_user_action,fk_contact) 
values ('2002-04-02',3,1,1,1,13);
insert into llx_actioncomm (datea, fk_action,fk_soc,fk_user_author,fk_user_action,fk_contact) 
values ('2002-03-05',3,1,1,1,13);
insert into llx_actioncomm (datea, fk_action,fk_soc,fk_user_author,fk_user_action,fk_contact) 
values ('2002-03-04',1,1,1,1,11);
insert into llx_actioncomm (datea, fk_action,fk_soc,fk_user_author,fk_user_action,fk_contact) 
values ('2001-03-05',1,1,1,1,11);
--
--
--
--

insert into llx_societe (nom,cp,ville,tel,fax,fournisseur,prefix_comm,datec)
values ('Peuplier','22300','Lanmérin','01 55 55 03 18','01 55 55 55 55',1,'JP',now());

insert into llx_societe (nom,cp,ville,tel,fax,fournisseur,prefix_comm,datec)
values ('Poirier','22290','Lannebert','01 55 55 03 18','01 55 55 55 55',1,'PO',now());

insert into llx_societe (nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values ('Orme','22400','Noyal','01 55 55 03 18','01 55 55 55 55',1,'ORM');

insert into llx_societe (nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values ('Pin','22200','Pabu','01 55 55 03 18','01 55 55 55 55',1,'PIN');

insert into llx_societe (nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values ('Merisier','22510','Penguily','01 55 55 03 18','01 55 55 55 55',1,'IKE');

insert into llx_societe (nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values ('Hêtre','22480','Peumerit-Quintin','01 55 55 03 18','01 55 55 55 55',1,'CAS');

insert into llx_societe (nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values ('Saule','22800','Quintin','01 55 55 03 18','01 55 55 55 55',1,'ME');

insert into llx_societe (nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values ('Tek','22300','Rospez','01 55 55 03 18','01 55 55 55 55',1,'LMT');
--
--
--
--


insert into llx_compta_account (datec, number, label, fk_user_author) values (now(),'431000','URSSAF',1);
insert into llx_compta_account (datec, number, label, fk_user_author) values (now(),'654000','Clients',1);

--
-- Charges sociales
--

insert into llx_chargesociales (date_ech,date_pai,libelle,fk_type,amount,paye,periode) values 
('2002-05-15',NULL,'Acompte 1er Trimestre 2002',1,120,0,'2002-1-1');

insert into llx_chargesociales (date_ech,date_pai,libelle,fk_type,amount,paye,periode) values 
('2002-05-15',NULL,'Acompte 1er Trimestre 2002',2,200,0,'2002-1-1');

insert into llx_chargesociales (date_ech,date_pai,libelle,fk_type,amount,paye,periode) values 
('2002-05-15',NULL,'Acompte 1er Trimestre 2002',3,170,0,'2002-1-1');

insert into llx_chargesociales (date_ech,date_pai,libelle,fk_type,amount,paye,periode) values 
('2002-02-15','2002-02-10','Acompte 4ème Trimestre 2001',1,120,1,'2001-10-1');

insert into llx_chargesociales (date_ech,date_pai,libelle,fk_type,amount,paye,periode) values 
('2002-02-15','2002-02-10','Acompte 4ème Trimestre 2001',2,200,1,'2001-10-1');

insert into llx_chargesociales (date_ech,date_pai,libelle,fk_type,amount,paye,periode) values 
('2002-02-15','2002-02-10','Acompte 4ème Trimestre 2001',3,170,1,'2001-10-1');

insert into llx_chargesociales (date_ech,date_pai,libelle,fk_type,amount,paye,periode) values 
('2001-11-15','2001-10-10','Acompte 3ème Trimestre 2001',1,70,1,'2001-7-1');

insert into llx_chargesociales (date_ech,date_pai,libelle,fk_type,amount,paye,periode) values 
('2001-11-15','2001-10-10','Acompte 3ème Trimestre 2001',2,180,1,'2001-7-1');

insert into llx_chargesociales (date_ech,date_pai,libelle,fk_type,amount,paye,periode) values 
('2001-11-15','2001-10-10','Acompte 3ème Trimestre 2001',3,150,1,'2001-7-1');

-- MySQL dump 9.09
--
-- Host: localhost    Database: dolibarr
-- -------------------------------------------------------
-- Server version	4.0.15-log

--
-- Dumping data for table `llx_bank`
--
INSERT INTO llx_bank (datec, datev, dateo, amount, label, fk_account, fk_user_author, fk_user_rappro, fk_type, num_releve, num_chq, rappro, note, author) 
VALUES (now(),'2002-01-13','2002-01-13',4000,'Dépôt liquide',1,1,1,'DEP',200201,NULL,1,NULL,NULL);
INSERT INTO llx_bank (datec, datev, dateo, amount, label, fk_account, fk_user_author, fk_user_rappro, fk_type, num_releve, num_chq, rappro, note, author) 
VALUES (now(),'2002-01-14','2002-01-14',-20,'Liquide',1,1,1,'CB',200201,NULL,1,NULL,NULL);
INSERT INTO llx_bank (datec, datev, dateo, amount, label, fk_account, fk_user_author, fk_user_rappro, fk_type, num_releve, num_chq, rappro, note, author) 
VALUES (now(),'2002-02-14','2002-02-14',-23.2,'Essence',1,1,1,'CB',200201,NULL,1,NULL,NULL);
INSERT INTO llx_bank (datec, datev, dateo, amount, label, fk_account, fk_user_author, fk_user_rappro, fk_type, num_releve, num_chq, rappro, note, author) 
VALUES (now(),'2002-02-15','2002-02-15',-53.32,'Cartouches imprimante',1,1,1,'CB',200202,NULL,0,NULL,NULL);
INSERT INTO llx_bank (datec, datev, dateo, amount, label, fk_account, fk_user_author, fk_user_rappro, fk_type, num_releve, num_chq, rappro, note, author) 
VALUES (now(),'2002-02-17','2002-02-17',-100,'Liquide',1,1,NULL,'CB',NULL,NULL,0,NULL,NULL);
INSERT INTO llx_bank (datec, datev, dateo, amount, label, fk_account, fk_user_author, fk_user_rappro, fk_type, num_releve, num_chq, rappro, note, author) 
VALUES (now(),'2002-02-18','2002-02-18',-153.32,'Restaurant',1,1,NULL,'CB',NULL,NULL,0,NULL,NULL);
INSERT INTO llx_bank (datec, datev, dateo, amount, label, fk_account, fk_user_author, fk_user_rappro, fk_type, num_releve, num_chq, rappro, note, author) 
VALUES (now(),'2002-02-20','2002-02-20',-1532,'Réparation climatisation',1,1,NULL,'CB',NULL,NULL,0,NULL,NULL);
INSERT INTO llx_bank (datec, datev, dateo, amount, label, fk_account, fk_user_author, fk_user_rappro, fk_type, num_releve, num_chq, rappro, note, author) 
VALUES (now(),'2002-02-21','2002-02-21',-100,'Liquide',1,1,NULL,'CB',NULL,NULL,0,NULL,NULL);
INSERT INTO llx_bank (datec, datev, dateo, amount, label, fk_account, fk_user_author, fk_user_rappro, fk_type, num_releve, num_chq, rappro, note, author) 
VALUES (now(),'2002-02-22','2002-02-22',-46,'Timbres postes',1,1,NULL,'CB',NULL,NULL,0,NULL,NULL);
INSERT INTO llx_bank (datec, datev, dateo, amount, label, fk_account, fk_user_author, fk_user_rappro, fk_type, num_releve, num_chq, rappro, note, author) 
VALUES (now(),'2002-03-02','2002-03-02',-60,'Liquide',1,1,NULL,'CB',NULL,NULL,0,NULL,NULL);
INSERT INTO llx_bank (datec, datev, dateo, amount, label, fk_account, fk_user_author, fk_user_rappro, fk_type, num_releve, num_chq, rappro, note, author) 
VALUES (now(),'2002-03-02','2002-03-02',-25.66,'Essence',1,1,NULL,'CB',NULL,NULL,0,NULL,NULL);
INSERT INTO llx_bank (datec, datev, dateo, amount, label, fk_account, fk_user_author, fk_user_rappro, fk_type, num_releve, num_chq, rappro, note, author) 
VALUES (now(),'2002-03-03','2002-03-03',-60,'Liquide',1,1,NULL,'CB',NULL,NULL,0,NULL,NULL);
INSERT INTO llx_bank (datec, datev, dateo, amount, label, fk_account, fk_user_author, fk_user_rappro, fk_type, num_releve, num_chq, rappro, note, author) 
VALUES (now(),'2002-03-04','2002-03-04',-15.2,'Café',1,1,NULL,'CB',NULL,NULL,0,NULL,NULL);
INSERT INTO llx_bank (datec, datev, dateo, amount, label, fk_account, fk_user_author, fk_user_rappro, fk_type, num_releve, num_chq, rappro, note, author) 
VALUES (now(),'2002-03-06','2002-03-06',-12.3,'Péage',1,1,NULL,'CB',NULL,NULL,0,NULL,NULL);
INSERT INTO llx_bank (datec, datev, dateo, amount, label, fk_account, fk_user_author, fk_user_rappro, fk_type, num_releve, num_chq, rappro, note, author) 
VALUES (now(),'2002-03-06','2002-03-06',-25.3,'Péage',1,1,NULL,'CB',NULL,NULL,0,NULL,NULL);
INSERT INTO llx_bank (datec, datev, dateo, amount, label, fk_account, fk_user_author, fk_user_rappro, fk_type, num_releve, num_chq, rappro, note, author) 
VALUES (now(),'2002-03-06','2002-03-06',-9.6,'Tickets de bus',1,1,NULL,'CB',NULL,NULL,0,NULL,NULL);
INSERT INTO llx_bank (datec, datev, dateo, amount, label, fk_account, fk_user_author, fk_user_rappro, fk_type, num_releve, num_chq, rappro, note, author) 
VALUES (now(),'2002-03-13','2002-03-13',-10,'Liquide',1,1,NULL,'CB',NULL,NULL,0,NULL,NULL);

-- MySQL dump 9.09
--
-- Host: localhost    Database: dolibarr
-- -------------------------------------------------------
-- Server version	4.0.15-log

--
-- Dumping data for table `llx_bank_account`
--
INSERT INTO llx_bank_account (rowid, datec, tms, label, bank, code_banque, code_guichet, number, cle_rib, bic, iban_prefix, domiciliation, courant, clos) VALUES (1,'2001-01-01 13:06:11',20031014173428,'CCP','La PosteToto','','','','','','','',1,0);

--
-- Energie
--
INSERT INTO llx_energie_compteur VALUES (1,'EDF Vitré',1,now(),1,'');
INSERT INTO llx_energie_compteur VALUES (2,'Eau Vitré',2,now(),1,'');
INSERT INTO llx_energie_compteur VALUES (3,'Gaz Vitré',3,now(),1,'');

INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-07-26',1139);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-07-21',1129);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-07-16',1128);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-07-11',1128);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-07-06',1128);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-07-01',1127);

INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-06-26',1127);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-06-21',1126);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-06-16',1116);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-06-11',1107);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-06-06',1097);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-06-01',1087);

INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-05-26',1078);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-05-21',1068);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-05-16',1059);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-05-11',1049);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-05-06',1038);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-05-01',1028);

INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-04-26',1013);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-04-21',1003);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-04-16',984);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-04-11',965);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-04-06',945);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-04-01',926);

INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-03-26',906);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-03-21',884);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-03-16',862);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-03-11',841);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-03-06',828);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-03-01',807);

INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-02-26',785);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-02-21',760);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-02-16',737);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-02-11',713);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-02-06',688);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-02-01',662);

INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-01-26',637);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-01-21',605);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-01-16',575);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-01-11',545);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-01-06',510);
INSERT INTO llx_energie_compteur_releve (fk_compteur, date_releve,valeur) VALUES (1,'2005-01-01',480);
