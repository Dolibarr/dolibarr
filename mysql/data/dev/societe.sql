-- ===========================================================================
-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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


--
-- Societe les fournisseurs sont sur les numéros pairs
--
delete from llx_societe;

insert into llx_societe (nom,datec,cp,ville,tel,fax, client, prefix_comm)
values ('Cumulo',now(),'56610','Arradon','01 40 15 03 18','01 40 15 06 18',1,'CU');

insert into llx_societe (nom,datec,cp,ville,tel,fax, client, prefix_comm, fournisseur)
values ('Bolix SA',now(),'56350','Allaire','01 40 15 03 18','01 40 15 06 18',1,'LO',1);

insert into llx_societe (nom,cp,ville,tel,fax,client, prefix_comm)
values ('Doli INC.','29300','Arzano','01 55 55 03 18','01 55 55 55 55',1,'DO');

insert into llx_societe (nom,cp,ville,tel,fax,client, prefix_comm,url, fournisseur)
values ('Foo SARL','22300','Ploubezre','01 55 55 03 18','01 55 55 55 55',1,'FOO','www.gnu.org',1);

insert into llx_societe (nom,datec,cp,ville,tel,fax, client, prefix_comm)
values ('Talphinfo',now(),'29400','Bodilis','01 40 15 03 18','01 40 15 06 18',1,'AP');

insert into llx_societe (idp,nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values (20,'Bouleau','22800','Le Foeil','01 55 55 03 18','01 55 55 55 55',1,'BTP');

insert into llx_societe (nom,datec,cp,ville,tel,fax, client, prefix_comm)
values ('Valphanix',now(),'29820','Bohars','01 40 15 03 18','01 40 15 06 18',1,'AL');

insert into llx_societe (idp,nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values (101,'Cerisier','22290','Goudelin','01 55 55 03 18','01 55 55 55 55',1,'CER');

insert into llx_societe (nom,cp,ville,tel,fax,client,url)
values ('Turin','29890','Brignogan-Plage','01 55 55 03 18','01 55 55 55 55',1,'http://www.ot-brignogan-plage.fr/');

insert into llx_societe (idp,nom,cp,ville,tel,fax,fournisseur,prefix_comm)
values (100,'Chêne','22330','Le Gouray','01 55 55 03 18','01 55 55 55 55',1,'DEL');

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


insert into llx_societe (nom,cp,ville,tel,fax,client)
values ('Bronite SARL','22110','Rostrenen','01 55 55 03 18','01 55 55 55 55',1);

insert into llx_societe (nom,cp,ville,tel,fax,client)
values ('Trioum','22110','Rostrenen','01 55 55 03 18','01 55 55 55 55',1);


insert into llx_societe (nom,cp,ville,tel,fax,client)
values ('KerTo','29490','Guipavas','01 55 55 03 18','01 55 55 55 55',1);

insert into llx_societe (nom,cp,ville,tel,fax,client)
values ('Keneah','29490','Guipavas','01 55 55 03 18','01 55 55 55 55',1);


insert into llx_societe (nom,cp,ville,tel,fax,client)
values ('Goeland SARL','29233','Cléder','01 55 55 03 18','01 55 55 55 55',1);

insert into llx_societe (nom,cp,ville,tel,fax,client)
values ('Pruni','29870','Coat-Méal','01 55 55 03 18','01 55 55 55 55',1);

insert into llx_societe (nom,cp,ville,tel,fax,client)
values ('Cumalon','29120','Combrit','01 55 55 03 18','01 55 55 55 55',1);

insert into llx_societe (nom,cp,ville,tel,fax,client)
values ('Naratzva','29490','Guipavas','01 55 55 03 18','01 55 55 55 55',1);

