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
--
-- Societe
--
delete from societe;
insert into societe (nom,datec,cp,ville,tel,fax, client, prefix_comm)
values ('Lolix',now(),'75001','Paris','01 40 15 03 18','01 40 15 06 18',1,'LO');

insert into societe (nom,datec,cp,ville,tel,fax, client, prefix_comm)
values ('Doli Inc.',now(),'56400','Auray','01 40 15 03 18','01 40 15 06 18',1,'DO');


insert into societe (nom,datec,cp,ville,tel,fax, client, prefix_comm)
values ('Alphinfo',now(),'75001','Paris','01 40 15 03 18','01 40 15 06 18',1,'AP');

insert into societe (nom,datec,cp,ville,tel,fax, client, prefix_comm)
values ('Alpha',now(),'75001','Paris','01 40 15 03 18','01 40 15 06 18',1,'AL');

insert into societe (nom,cp,ville,tel,fax,client)
values ('Easter-Eggs','75013','Paris','01 55 55 03 18','01 55 55 55 55',1);


insert into societe (nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values ('JPG','75013','Paris','01 55 55 03 18','01 55 55 55 55',1,'JP');
--
-- Contact
--
insert into socpeople (fk_soc, name, firstname, phone,fax,email)
values (1,'Durand','Paul','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net');
insert into socpeople (fk_soc, name, firstname, phone,fax,email)
values (1,'Tourin','Pierre','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net');
insert into socpeople (fk_soc, name, firstname, phone,fax,email)
values (2,'Corin','Arnaud','01 40 15 03 18','01 40 15 06 18','dev@lafrere.net');
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
--
-- Fichinter
--
--
delete from llx_fichinter;
insert into llx_fichinter (fk_soc, ref, datec, date_valid, datei, fk_user_author, fk_user_valid, fk_statut, duree, note)
values (1, 'FI-LP-1','2001-12-05','2001-12-05','2001-12-05',1,1,1,4,'Mise à jour de la doc');

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
