-- ========================================================================
-- Copyright (C) 2001-2002,2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
-- ========================================================================

create table llx_c_regions
(
  code_region integer PRIMARY KEY,
  cheflieu    varchar(7),
  tncc        integer,
  nom         varchar(50)
)type=innodb;


insert into llx_c_regions values (01,'97105',3,'Guadeloupe');
insert into llx_c_regions values (02,'97209',3,'Martinique');
insert into llx_c_regions values (03,'97302',3,'Guyane');
insert into llx_c_regions values (04,'97411',3,'Réunion');
insert into llx_c_regions values (11,'75056',1,'Île-de-France');
insert into llx_c_regions values (21,'51108',0,'Champagne-Ardenne');
insert into llx_c_regions values (22,'80021',0,'Picardie');
insert into llx_c_regions values (23,'76540',0,'Haute-Normandie');
insert into llx_c_regions values (24,'45234',2,'Centre');
insert into llx_c_regions values (25,'14118',0,'Basse-Normandie');
insert into llx_c_regions values (26,'21231',0,'Bourgogne');
insert into llx_c_regions values (31,'59350',2,'Nord-Pas-de-Calais');
insert into llx_c_regions values (41,'57463',0,'Lorraine');
insert into llx_c_regions values (42,'67482',1,'Alsace');
insert into llx_c_regions values (43,'25056',0,'Franche-Comté');
insert into llx_c_regions values (52,'44109',4,'Pays de la Loire');
insert into llx_c_regions values (53,'35238',0,'Bretagne');
insert into llx_c_regions values (54,'86194',2,'Poitou-Charentes');
insert into llx_c_regions values (72,'33063',1,'Aquitaine');
insert into llx_c_regions values (73,'31555',0,'Midi-Pyrénées');
insert into llx_c_regions values (74,'87085',2,'Limousin');
insert into llx_c_regions values (82,'69123',2,'Rhône-Alpes');
insert into llx_c_regions values (83,'63113',1,'Auvergne');
insert into llx_c_regions values (91,'34172',2,'Languedoc-Roussillon');
insert into llx_c_regions values (93,'13055',0,'Provence-Alpes-Côte d\'Azur');
insert into llx_c_regions values (94,'2A004',0,'Corse');