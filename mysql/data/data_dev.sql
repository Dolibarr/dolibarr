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
values ('demo','demo','demo','demo','demo',1,1,'demo');

insert into llx_user (name,firstname,code,login,pass,module_comm,module_compta,webcal_login)
values ('demo1','demo','demo1','demo1','demo',1,1,'demo1');
insert into llx_user (name,firstname,code,login,pass,module_comm,module_compta,webcal_login)
values ('demo2','demo','demo2','demo2','demo',1,1,'demo2');
--
-- Societe
--
delete from societe;
insert into societe (nom,datec,cp,ville,tel,fax, client, prefix_comm, fournisseur)
values ('Lolix',now(),'75001','Paris','01 40 15 03 18','01 40 15 06 18',1,'LO',1);

insert into societe (nom,datec,cp,ville,tel,fax, client, prefix_comm)
values ('Doli Inc.',now(),'56400','Auray','01 40 15 03 18','01 40 15 06 18',1,'DO');

insert into societe (nom,cp,ville,tel,fax,client)
values ('Easter-Eggs','75013','Paris','01 55 55 03 18','01 55 55 55 55',1);

insert into societe (nom,datec,cp,ville,tel,fax, client, prefix_comm)
values ('Alphinfo',now(),'75001','Paris','01 40 15 03 18','01 40 15 06 18',1,'AP');

insert into societe (nom,datec,cp,ville,tel,fax, client, prefix_comm)
values ('Alpha',now(),'75001','Loctudy','01 40 15 03 18','01 40 15 06 18',1,'AL');

insert into societe (nom,cp,ville,tel,fax,client)
values ('Turin','75013','Montpellier','01 55 55 03 18','01 55 55 55 55',1);

insert into societe (nom,cp,ville,tel,fax,client)
values ('Bratin SA','75013','Strasbourg','01 55 55 03 18','01 55 55 55 55',1);

insert into societe (nom,cp,ville,tel,fax,client)
values ('Eaggos SARL','75013','Auray','01 55 55 03 18','01 55 55 55 55',1);

insert into societe (nom,cp,ville,tel,fax,client)
values ('Pruitosa','75013','Lorient','01 55 55 03 18','01 55 55 55 55',1);

insert into societe (nom,cp,ville,tel,fax,client)
values ('ZXP Tune','75013','Vannes','01 55 55 03 18','01 55 55 55 55',1);




--
-- Contact
--
delete from socpeople;
insert into socpeople (fk_soc, name, firstname, phone,fax,email)
values (1,'Durand','Paul','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net');
insert into socpeople (fk_soc, name, firstname, phone,fax,email)
values (1,'Tourin','Pierre','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net');
insert into socpeople (fk_soc, name, firstname, phone,fax,email)
values (1,'Patrick','Paul','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net');
insert into socpeople (fk_soc, name, firstname, phone,fax,email)
values (1,'Myriam','Isabelle','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net');

insert into socpeople (fk_soc, name, firstname, phone,fax,email)
values (2,'Corin','Arnaud','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net');
insert into socpeople (fk_soc, name, firstname, phone,fax,email)
values (3,'Phil','Breizh','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net');
insert into socpeople (fk_soc, name, firstname, phone,fax,email)
values (3,'Marie','Jeanne','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net');
--
--
-- Produits
--
--
delete from llx_product;
insert into llx_product (ref, label, description, price, duration)
values ('CC-2M','Compilo','Compilateur GCC',10,'1 mois');

insert into llx_product (ref, label, description, price, duration)
values ('CC-2M','Config Alpha','Configurations a base de proc alpha',1000,'1 mois');
--
-- Propale
--
delete from llx_propal;
insert into llx_propal values (1,1,1,0,'PR-LO-020403','2002-04-03 13:44:04','2002-04-03 15:45:29',NULL,'2002-04-03',2,2,NULL,1,1010,0,197.96,1207.96,'');
insert into llx_propal values (2,1,1,0,'PR-LO-020303','2002-03-03 13:44:04','2002-03-03 15:45:29',NULL,'2002-03-03',2,2,NULL,1,400,0,78.4,478.40,'');

insert into llx_propal values (3,1,1,0,'PR-LO-010303','2001-03-03 13:44:04','2001-03-03 15:45:29',NULL,'2001-03-03',2,2,NULL,3,400,0,78.4,478.40,'');
insert into llx_propal values (4,2,1,0,'PR-DO-010303','2001-03-03 13:44:04','2001-03-03 15:45:29',NULL,'2001-03-03',2,2,NULL,2,400,0,78.4,478.40,'');
insert into llx_propal values (5,1,1,0,'PR-LO-010301','2001-03-01 13:44:04','2001-03-01 15:45:29',NULL,'2001-03-01',2,2,NULL,2,400,0,78.4,478.40,'');
insert into llx_propal values (6,1,1,0,'PR-LO-020301','2002-03-01 13:44:04','2002-03-01 15:45:29',NULL,'2002-03-01',2,2,NULL,2,400,0,78.4,478.40,'');
insert into llx_propal values (7,1,1,0,'PR-LO-010307','2001-07-01 13:44:04','2001-07-01 15:45:29',NULL,'2001-07-01',2,2,NULL,2,4000,0,784,4784,'');
--
-- Factures
--
delete from llx_facture;
INSERT INTO llx_facture VALUES (1,'F-LO-020502',1,'2002-05-06 18:28:34','2002-05-02',1,400,0,78.4,478.4,1,'rodo',NULL,NULL,NULL,'');
INSERT INTO llx_facture VALUES (2,'F-LO-020310',1,'2002-05-06 18:29:37','2002-03-10',1,4000,0,784,4784,1,'rodo',NULL,NULL,NULL,'');
INSERT INTO llx_facture VALUES (3,'F-LO-020506',1,'2002-05-06 18:33:37','2002-05-06',0,400,0,78.4,478.4,0,'rodo',NULL,NULL,NULL,'');
--
-- Paiements
--
delete from llx_paiement;
INSERT INTO llx_paiement VALUES (1,1,'2002-05-06 18:28:54','2002-05-06 12:00:00',478.4,'rodo',0,'4322222','');
INSERT INTO llx_paiement VALUES (2,2,'2002-05-06 18:30:03','2002-04-06 12:00:00',2500,'rodo',0,'3245','');
INSERT INTO llx_paiement VALUES (3,2,'2002-05-06 18:32:59','2002-05-02 12:00:00',2284,'rodo',0,'87645','');

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
values (20,'Bouleau','75013','Paris','01 55 55 03 18','01 55 55 55 55',1,'BTP');
insert into societe (idp,nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values (101,'Cerisier','75013','Paris','01 55 55 03 18','01 55 55 55 55',1,'CER');

insert into societe (idp,nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values (100,'Chêne','75013','Paris','01 55 55 03 18','01 55 55 55 55',1,'DEL');

insert into societe (nom,cp,ville,tel,fax,fournisseur,prefix_comm,datec)
values ('Peuplier','75013','Paris','01 55 55 03 18','01 55 55 55 55',1,'JP',now());
insert into societe (nom,cp,ville,tel,fax,fournisseur,prefix_comm,datec)
values ('Poirier','75013','Paris','01 55 55 03 18','01 55 55 55 55',1,'PO',now());
insert into societe (nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values ('Orme','75013','Paris','01 55 55 03 18','01 55 55 55 55',1,'ORM');
insert into societe (nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values ('Pin','27500','Pont-Audemer','01 55 55 03 18','01 55 55 55 55',1,'PIN');
insert into societe (nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values ('Merisier','75013','Paris','01 55 55 03 18','01 55 55 55 55',1,'IKE');
insert into societe (nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values ('Hêtre','75013','Paris','01 55 55 03 18','01 55 55 55 55',1,'CAS');
insert into societe (nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values ('Saule','75013','Paris','01 55 55 03 18','01 55 55 55 55',1,'ME');
insert into societe (nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values ('Poirier','75013','Paris','01 55 55 03 18','01 55 55 55 55',1,'CEG');
insert into societe (nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values ('Tek','75013','Paris','01 55 55 03 18','01 55 55 55 55',1,'LMT');


