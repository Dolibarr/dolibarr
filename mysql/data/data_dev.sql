-- ===========================================================================
-- Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
-- Valeurs de test pour les developpements
-- Ne pas hésiter a compléter ce fichier avec de nouvelles valeurs, plus on a
-- de données, mieux on peut tester l'appli.
-- ===========================================================================
delete from llx_tva;
insert into llx_tva (datep, datev, amount) values ('2001-11-11','2001-10-01', 1960.00);
insert into llx_tva (datep, datev, amount) values ('2001-04-11','2001-01-01', 2000.00);

delete from llx_facture_fourn;

insert into llx_facture_fourn (facnumber, fk_soc, datec, datef, paye, amount, remise, tva, total, fk_statut, fk_user_author, fk_user_valid, note) 
values ('LOL-509',1,'2001-05-09','2001-05-09',1,1000,0,196,1196,1,NULL,NULL,'');
insert into llx_facture_fourn (facnumber, fk_soc, datec, datef, paye, amount, remise, tva, total, fk_statut, fk_user_author, fk_user_valid, note) 
values ('LOL-510',1,'2001-09-09','2001-09-09',1,100,0,19.6,119.6,1,NULL,NULL,'');

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

REPLACE INTO llx_const (name, value, type, visible) VALUES ('MAIN_THEME',  'yellow','chaine',1);

REPLACE INTO llx_const (name, value, type, visible) VALUES ('MAIN_MODULE_BOUTIQUE',  '1','yesno',0);
REPLACE INTO llx_const (name, value, type, visible) VALUES ('MAIN_MODULE_COMMANDE',  '1','yesno',0);
REPLACE INTO llx_const (name, value, type, visible) VALUES ('MAIN_MODULE_COMMERCIAL','1','yesno',0);
REPLACE INTO llx_const (name, value, type, visible) VALUES ('MAIN_MODULE_FACTURE',   '1','yesno',0);

delete from llx_user;

insert into llx_user (name,firstname,code,login,pass,module_comm,module_compta,webcal_login,admin)
values ('demo','demo','DEMO','demo','demo',1,1,'demo',1);

insert into llx_user (name,firstname,code,login,pass,module_comm,module_compta,webcal_login)
values ('demo1','demo1','DM1','demo1','demo',1,1,'demo1');

insert into llx_user (name,firstname,code,login,pass,module_comm,module_compta,webcal_login)
values ('demo2','demo2','DM2','demo2','demo',1,1,'demo2');
--
-- Societe
--
delete from llx_societe;
insert into llx_societe (nom,datec,cp,ville,tel,fax, client, prefix_comm, fournisseur)
values ('Bolix SA',now(),'56350','Allaire','01 40 15 03 18','01 40 15 06 18',1,'LO',1);

insert into llx_societe (nom,datec,cp,ville,tel,fax, client, prefix_comm)
values ('Cumulo',now(),'56610','Arradon','01 40 15 03 18','01 40 15 06 18',1,'CU');

insert into llx_societe (nom,cp,ville,tel,fax,client, prefix_comm)
values ('Doli INC.','29300','Arzano','01 55 55 03 18','01 55 55 55 55',1,'DO');

insert into llx_societe (nom,cp,ville,tel,fax,client, prefix_comm,url)
values ('Foo SARL','22300','Ploubezre','01 55 55 03 18','01 55 55 55 55',1,'FOO','www.gnu.org');

insert into llx_societe (nom,datec,cp,ville,tel,fax, client, prefix_comm)
values ('Talphinfo',now(),'29400','Bodilis','01 40 15 03 18','01 40 15 06 18',1,'AP');

insert into llx_societe (nom,datec,cp,ville,tel,fax, client, prefix_comm)
values ('Valphanix',now(),'29820','Bohars','01 40 15 03 18','01 40 15 06 18',1,'AL');

insert into llx_societe (nom,cp,ville,tel,fax,client,url)
values ('Turin','29890','Brignogan-Plage','01 55 55 03 18','01 55 55 55 55',1,'http://www.ot-brignogan-plage.fr/');

insert into llx_societe (nom,cp,ville,tel,fax,client)
values ('Yratin SA','29660','Carantec','01 55 55 03 18','01 55 55 55 55',1);

insert into llx_societe (nom,cp,ville,tel,fax,client)
values ('Raggos SARL','29233','Cléder','01 55 55 03 18','01 55 55 55 55',1);

insert into llx_societe (nom,cp,ville,tel,fax,client)
values ('Pruitosa','29870','Coat-Méal','01 55 55 03 18','01 55 55 55 55',1);

insert into llx_societe (nom,cp,ville,tel,fax,client)
values ('Stratus','29120','Combrit','01 55 55 03 18','01 55 55 55 55',1);

insert into llx_societe (nom,cp,ville,tel,fax,client)
values ('Nimbus','29490','Guipavas','01 55 55 03 18','01 55 55 55 55',1);

insert into llx_societe (nom,cp,ville,tel,fax,client)
values ('Iono','22110','Rostrenen','01 55 55 03 18','01 55 55 55 55',1);
--
-- Contact
--
delete from llx_socpeople;
insert into llx_socpeople (idp,fk_soc, name, firstname, phone,fax,email)
values (10,1,'Victoire','Paul','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net');
insert into llx_socpeople (idp,fk_soc, name, firstname, phone,fax,email)
values (11,1,'Tourin','Pierre','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net');
insert into llx_socpeople (idp,fk_soc, name, firstname, phone,fax,email)
values (12,1,'Patrick','Paul','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net');
insert into llx_socpeople (idp,fk_soc, name, firstname, phone,fax,email)
values (13,1,'Myriam','Isabelle','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net');

insert into llx_socpeople (idp,fk_soc, name, firstname, phone,fax,email)
values (20,2,'Corin','Arnaud','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net');
insert into llx_socpeople (idp,fk_soc, name, firstname, phone,fax,email)
values (30,3,'Phil','Breizh','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net');
insert into llx_socpeople (idp,fk_soc, name, firstname, phone,fax,email)
values (31,3,'Marie','Jeanne','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net');
insert into llx_socpeople (idp,fk_soc, name, firstname, phone,fax,email)
values (41,4,'Alix','Victor','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net');
--
--
-- Produits
--
--
delete from llx_product;

insert into llx_product (ref, label, description, price, tva_tx)
values ('RJ452M','Câble Réseaux RJ45 2m','Câble Réseaux RJ45 2m',10,19.6);

insert into llx_product (ref, label, description, price, tva_tx)
values ('RJ458M','Câble Réseaux RJ45 8m','Câble Réseaux RJ45 8m',10,19.6);

insert into llx_product (ref, label, description, price, tva_tx)
values ('RJ4515M','Câble Réseaux RJ45 15m','Câble Réseaux RJ45 15m',10,19.6);

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

--
--
-- Fichinter
--
--
delete from llx_fichinter;
insert into llx_fichinter (fk_soc, ref, datec, date_valid, datei, fk_user_author, fk_user_valid, fk_statut, duree, note)
values (1, 'FI-LP-1','2001-12-05','2001-12-05','2001-12-05',1,1,1,4,'Mise à jour de la doc');
--
-- Actions commerciales
--
delete from llx_actioncomm;
insert into llx_actioncomm (datea, fk_action,fk_soc,fk_user_author,fk_contact) 
values ('2002-04-06',1,1,1,1);
insert into llx_actioncomm (datea, fk_action,fk_soc,fk_user_author,fk_contact) 
values ('2002-04-05',2,1,1,1);
insert into llx_actioncomm (datea, fk_action,fk_soc,fk_user_author,fk_contact) 
values ('2002-04-05',1,1,1,1);
insert into llx_actioncomm (datea, fk_action,fk_soc,fk_user_author,fk_contact) 
values ('2002-04-02',3,1,1,1);
insert into llx_actioncomm (datea, fk_action,fk_soc,fk_user_author,fk_contact) 
values ('2002-04-02',3,1,1,1);
insert into llx_actioncomm (datea, fk_action,fk_soc,fk_user_author,fk_contact) 
values ('2002-03-05',3,1,1,1);
insert into llx_actioncomm (datea, fk_action,fk_soc,fk_user_author,fk_contact) 
values ('2002-03-04',1,1,1,1);
insert into llx_actioncomm (datea, fk_action,fk_soc,fk_user_author,fk_contact) 
values ('2001-03-05',1,1,1,1);
--
--
--
--
insert into llx_societe (idp,nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values (20,'Bouleau','22800','Le Foeil','01 55 55 03 18','01 55 55 55 55',1,'BTP');

insert into llx_societe (idp,nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values (101,'Cerisier','22290','Goudelin','01 55 55 03 18','01 55 55 55 55',1,'CER');

insert into llx_societe (idp,nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values (100,'Chêne','22330','Le Gouray','01 55 55 03 18','01 55 55 55 55',1,'DEL');

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
values ('Poirier','22940','Plaintel','01 55 55 03 18','01 55 55 55 55',1,'CEG');

insert into llx_societe (nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values ('Tek','22300','Rospez','01 55 55 03 18','01 55 55 55 55',1,'LMT');
--
--
--
--
delete from llx_service;

insert into llx_service values ( 1,now(),now(),'FDEVC1','Forfait Dev, CAT. 1','',500,NULL,now(),NULL,2,2);
insert into llx_service values ( 2,now(),now(),'FDEVC2','Forfait Dev, CAT. 2','',700,NULL,now(),NULL,2,2);
insert into llx_service values ( 3,now(),now(),'FDEVC3','Forfait Dev, CAT. 3','',900,NULL,now(),NULL,2,2);

insert into llx_service values ( 4,now(),now(),'FADMC1','Forfait Adm, CAT. 1','',600,NULL,now(),NULL,2,2);
insert into llx_service values ( 5,now(),now(),'FADMC2','Forfait Adm, CAT. 2','',800,NULL,now(),NULL,2,2);

insert into llx_service values ( 6,now(),now(),'FAUDC1','Forfait Aud, CAT. 2','',800,NULL,now(),NULL,2,2);
insert into llx_service values ( 7,now(),now(),'FAUDC2','Forfait Aud, CAT. 3','',1000,NULL,now(),NULL,2,2);


insert into llx_service values ( 8,now(),now(),'RDEVC1','Régie Dev, CAT. 1','',400,NULL,now(),NULL,2,2);
insert into llx_service values ( 9,now(),now(),'RDEVC2','Régie Dev, CAT. 2','',600,NULL,now(),NULL,2,2);
insert into llx_service values (10,now(),now(),'RDEVC3','Régie Dev, CAT. 3','',800,NULL,now(),NULL,2,2);

insert into llx_service values (11,now(),now(),'RADMC1','Régie Adm, CAT. 1','',500,NULL,now(),NULL,2,2);
insert into llx_service values (12,now(),now(),'RADMC2','Régie Adm, CAT. 2','',700,NULL,now(),NULL,2,2);

insert into llx_service values (13,now(),now(),'RAUDC1','Régie Aud, CAT. 2','',700,NULL,now(),NULL,2,2);
insert into llx_service values (14,now(),now(),'RAUDC2','Régie Aud, CAT. 3','',900,NULL,now(),NULL,2,2);


delete from llx_propal;
delete from llx_propaldet;
delete from llx_facture;
delete from llx_paiement;


delete from llx_compta_account;
insert into llx_compta_account (datec, number, label, fk_user_author) values (now(),'431000','URSSAF',1);
insert into llx_compta_account (datec, number, label, fk_user_author) values (now(),'654000','Clients',1);
--
-- Charges sociales (mais non on n'en paye pas trop ;-)
--
delete from llx_chargesociales;
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

