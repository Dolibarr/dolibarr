-- ===========================================================================
--
-- Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
-- Valeurs de test pour les devellopements
--
-- ===========================================================================

delete from llx_user;
insert into llx_user (name,firstname,code,login,pass,module_comm,module_compta,admin,webcal_login)
values ('Quiedeville','Rodolphe','RQ','rodo','rodo',1,1,1,'rodo');

insert into llx_user (name,firstname,code,login,pass,module_comm,module_compta,webcal_login)
values ('demo','demo','DEMO','demo','demo',1,0,'demo');

insert into llx_user (name,firstname,code,login,pass,module_comm,module_compta,webcal_login)
values ('demo1','demo1','DM1','demo1','demo',1,0,'demo1');
insert into llx_user (name,firstname,code,login,pass,module_comm,module_compta,webcal_login)
values ('demo2','demo2','DM2','demo2','demo',1,0,'demo2');
--
-- Societe
--
delete from societe;
insert into societe (nom,datec,cp,ville,tel,fax, client, prefix_comm, fournisseur)
values ('Bolix SA',now(),'56350','Allaire','01 40 15 03 18','01 40 15 06 18',1,'LO',1);

insert into societe (nom,datec,cp,ville,tel,fax, client, prefix_comm)
values ('Cumulo',now(),'56610','Arradon','01 40 15 03 18','01 40 15 06 18',1,'CU');

insert into societe (nom,cp,ville,tel,fax,client, prefix_comm)
values ('Doli INC.','29300','Arzano','01 55 55 03 18','01 55 55 55 55',1,'DO');

insert into societe (nom,cp,ville,tel,fax,client, prefix_comm,url)
values ('Foo SARL','22300','Ploubezre','01 55 55 03 18','01 55 55 55 55',1,'FOO','www.gnu.org');

insert into societe (nom,datec,cp,ville,tel,fax, client, prefix_comm)
values ('Talphinfo',now(),'29400','Bodilis','01 40 15 03 18','01 40 15 06 18',1,'AP');

insert into societe (nom,datec,cp,ville,tel,fax, client, prefix_comm)
values ('Valphanix',now(),'29820','Bohars','01 40 15 03 18','01 40 15 06 18',1,'AL');

insert into societe (nom,cp,ville,tel,fax,client,url)
values ('Turin','29890','Brignogan-Plage','01 55 55 03 18','01 55 55 55 55',1,'http://www.ot-brignogan-plage.fr/');

insert into societe (nom,cp,ville,tel,fax,client)
values ('Yratin SA','29660','Carantec','01 55 55 03 18','01 55 55 55 55',1);

insert into societe (nom,cp,ville,tel,fax,client)
values ('Raggos SARL','29233','Cléder','01 55 55 03 18','01 55 55 55 55',1);

insert into societe (nom,cp,ville,tel,fax,client)
values ('Pruitosa','29870','Coat-Méal','01 55 55 03 18','01 55 55 55 55',1);

insert into societe (nom,cp,ville,tel,fax,client)
values ('Stratus','29120','Combrit','01 55 55 03 18','01 55 55 55 55',1);

insert into societe (nom,cp,ville,tel,fax,client)
values ('Nimbus','29490','Guipavas','01 55 55 03 18','01 55 55 55 55',1);

insert into societe (nom,cp,ville,tel,fax,client)
values ('Iono','22110','Rostrenen','01 55 55 03 18','01 55 55 55 55',1);


--
-- Contact
--
delete from socpeople;
insert into socpeople (idp,fk_soc, name, firstname, phone,fax,email)
values (10,1,'Victoire','Paul','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net');
insert into socpeople (idp,fk_soc, name, firstname, phone,fax,email)
values (11,1,'Tourin','Pierre','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net');
insert into socpeople (idp,fk_soc, name, firstname, phone,fax,email)
values (12,1,'Patrick','Paul','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net');
insert into socpeople (idp,fk_soc, name, firstname, phone,fax,email)
values (13,1,'Myriam','Isabelle','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net');

insert into socpeople (idp,fk_soc, name, firstname, phone,fax,email)
values (20,2,'Corin','Arnaud','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net');
insert into socpeople (idp,fk_soc, name, firstname, phone,fax,email)
values (30,3,'Phil','Breizh','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net');
insert into socpeople (idp,fk_soc, name, firstname, phone,fax,email)
values (31,3,'Marie','Jeanne','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net');
insert into socpeople (idp,fk_soc, name, firstname, phone,fax,email)
values (41,4,'Alix','Victor','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net');
--
--
-- Produits
--
--
delete from llx_product;

insert into llx_product (ref, label, description, price, duration)
values ('CRRJ452M','Câble Réseaux RJ45 2m','Câble Réseaux RJ45 2m',10,'1 mois');

insert into llx_product (ref, label, description, price, duration)
values ('3COMSW8','Switch Cisco 8 ports 100Mbits','Switch Cisco 8 ports 100Mbits',1000,'1 mois');

insert into llx_product (ref, label, description, price, duration)
values ('ALPH','Station Alpha Serie 3w','Configuration Alpha',9750,'1 mois');

insert into llx_product (ref, label, description, price, duration)
values ('HUB8-10','Hub 8 ports 10Mbits','Hub 8 ports',750,'1 mois');

insert into llx_product (ref, label, description, price, duration)
values ('PB-16','Pan. Brass. 16','Panneau de brassage extensible, incluant 1 barre de 16 prises',650,'1 mois');

insert into llx_product (ref, label, description, price, duration)
values ('PB-32','Pan. Brass. 32','Panneau de brassage extensible, incluant 2 barres de 16 prises',1200,'1 mois');

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
delete from actioncomm;
insert into actioncomm (datea, fk_action,fk_soc,fk_user_author,fk_contact) 
values ('2002-04-06',1,1,1,1);
insert into actioncomm (datea, fk_action,fk_soc,fk_user_author,fk_contact) 
values ('2002-04-05',2,1,1,1);
insert into actioncomm (datea, fk_action,fk_soc,fk_user_author,fk_contact) 
values ('2002-04-05',1,1,1,1);
insert into actioncomm (datea, fk_action,fk_soc,fk_user_author,fk_contact) 
values ('2002-04-02',3,1,1,1);
insert into actioncomm (datea, fk_action,fk_soc,fk_user_author,fk_contact) 
values ('2002-04-02',3,1,1,1);
insert into actioncomm (datea, fk_action,fk_soc,fk_user_author,fk_contact) 
values ('2002-03-05',3,1,1,1);
insert into actioncomm (datea, fk_action,fk_soc,fk_user_author,fk_contact) 
values ('2002-03-04',1,1,1,1);
insert into actioncomm (datea, fk_action,fk_soc,fk_user_author,fk_contact) 
values ('2001-03-05',1,1,1,1);
--
--
--
--
insert into societe (idp,nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values (20,'Bouleau','22800','Le Foeil','01 55 55 03 18','01 55 55 55 55',1,'BTP');

insert into societe (idp,nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values (101,'Cerisier','22290','Goudelin','01 55 55 03 18','01 55 55 55 55',1,'CER');

insert into societe (idp,nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values (100,'Chêne','22330','Le Gouray','01 55 55 03 18','01 55 55 55 55',1,'DEL');

insert into societe (nom,cp,ville,tel,fax,fournisseur,prefix_comm,datec)
values ('Peuplier','22300','Lanmérin','01 55 55 03 18','01 55 55 55 55',1,'JP',now());

insert into societe (nom,cp,ville,tel,fax,fournisseur,prefix_comm,datec)
values ('Poirier','22290','Lannebert','01 55 55 03 18','01 55 55 55 55',1,'PO',now());

insert into societe (nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values ('Orme','22400','Noyal','01 55 55 03 18','01 55 55 55 55',1,'ORM');

insert into societe (nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values ('Pin','22200','Pabu','01 55 55 03 18','01 55 55 55 55',1,'PIN');

insert into societe (nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values ('Merisier','22510','Penguily','01 55 55 03 18','01 55 55 55 55',1,'IKE');

insert into societe (nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values ('Hêtre','22480','Peumerit-Quintin','01 55 55 03 18','01 55 55 55 55',1,'CAS');

insert into societe (nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values ('Saule','22800','Quintin','01 55 55 03 18','01 55 55 55 55',1,'ME');

insert into societe (nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values ('Poirier','22940','Plaintel','01 55 55 03 18','01 55 55 55 55',1,'CEG');

insert into societe (nom,cp,ville,tel,fax,fournisseur,prefix_comm)
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