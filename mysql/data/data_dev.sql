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
-- Valeurs pour les bases de langues francaises
--

delete from llx_user;
insert into llx_user (name,firstname,code,login,pass,module_comm,module_compta)
values ('Quiedeville','Rodolphe','RQ','rodo','rodo',1,1);

insert into llx_user (name,firstname,code,login,pass,module_comm,module_compta)
values ('demo','demo','demo','demo','demo',1,1);

delete from societe;
insert into societe (nom,datec,cp,ville,tel,fax, client)
values ('Lolix',now(),'75001','Paris','01 40 15 03 18','01 40 15 06 18',1);

insert into societe (nom,cp,ville,tel,fax,client)
values ('Easter-Eggs','75013','Paris','01 55 55 03 18','01 55 55 55 55',1);

insert into societe (nom,cp,ville,tel,fax,fournisseur)
values ('JPG','75013','Paris','01 55 55 03 18','01 55 55 55 55',1);

delete from llx_product;
insert into llx_product (ref, label, description, price, duration)
values ('CC-2M','Compilo','Compilateur GCC',10,'1 mois');

insert into llx_product (ref, label, description, price, duration)
values ('CC-2M','Config Alpha','Configurations a base de proc alpha',1000,'1 mois');

delete from llx_propal;
insert into llx_propal values (1,1,1,0,'PR-LO-020403','2002-04-03 13:44:04','2002-04-03 15:45:29',NULL,'2002-04-03',2,2,NULL,1,1010,0,197.96,1207.96,'');


delete from llx_fichinter;
insert into llx_fichinter (fk_soc, ref, datec, date_valid, datei, fk_user_author, fk_user_valid, fk_statut, duree, note)
values (1, 'FI-LP-1','2001-12-05','2001-12-05','2001-12-05',1,1,1,4,'Mise à jour de la doc');
