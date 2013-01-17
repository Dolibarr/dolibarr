-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
-- Copyright (C) 2010-2011 Juanjo Menent        <jmenent@2byte.es>
-- Copyright (C) 2012      Sebastian Neuwert    <sebastian.neuwert@modula71.de>
-- Copyright (C) 2012	   Ricardo Schluter     <info@ripasch.nl>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

--
-- Regions
--

insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (1,0,0,'0',0,'-');

-- Regions France (id country=1)
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
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 125, 1,  93,'13055',0,'Provence-Alpes-Côte d''Azur');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 126, 1,  94,'2A004',0,'Corse');

-- Regions Belgium (id country=2)
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 201, 2, 201,     '',1,'Flandre');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 202, 2, 202,     '',2,'Wallonie');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values ( 203, 2, 203,     '',3,'Bruxelles-Capitale');

-- Regions Italy (id country=3)
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (301, 3, 301, NULL, 1, 'Abruzzo');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (302, 3, 302, NULL, 1, 'Basilicata');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (303, 3, 303, NULL, 1, 'Calabria');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (304, 3, 304, NULL, 1, 'Campania');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (305, 3, 305, NULL, 1, 'Emilia-Romagna');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (306, 3, 306, NULL, 1, 'Friuli-Venezia Giulia');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (307, 3, 307, NULL, 1, 'Lazio');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (308, 3, 308, NULL, 1, 'Liguria');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (309, 3, 309, NULL, 1, 'Lombardia');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (310, 3, 310, NULL, 1, 'Marche');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (311, 3, 311, NULL, 1, 'Molise');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (312, 3, 312, NULL, 1, 'Piemonte');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (313, 3, 313, NULL, 1, 'Puglia');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (314, 3, 314, NULL, 1, 'Sardegna');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (315, 3, 315, NULL, 1, 'Sicilia');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (316, 3, 316, NULL, 1, 'Toscana');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (317, 3, 317, NULL, 1, 'Trentino-Alto Adige');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (318, 3, 318, NULL, 1, 'Umbria');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (319, 3, 319, NULL, 1, 'Valle d Aosta');
insert into llx_c_regions (rowid,fk_pays,code_region,cheflieu,tncc,nom) values (320, 3, 320, NULL, 1, 'Veneto');

-- Regions Spain (id country=4)
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (401,  4, 401, '', 0, 'Andalucia', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (402,  4, 402, '', 0, 'Aragón', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (403,  4, 403, '', 0, 'Castilla y León', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (404,  4, 404, '', 0, 'Castilla la Mancha', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (405,  4, 405, '', 0, 'Canarias', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (406,  4, 406, '', 0, 'Cataluña', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (407,  4, 407, '', 0, 'Comunidad de Ceuta', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (408,  4, 408, '', 0, 'Comunidad Foral de Navarra', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (409,  4, 409, '', 0, 'Comunidad de Melilla', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (410,  4, 410, '', 0, 'Cantabria', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (411,  4, 411, '', 0, 'Comunidad Valenciana', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (412,  4, 412, '', 0, 'Extemadura', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (413,  4, 413, '', 0, 'Galicia', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (414,  4, 414, '', 0, 'Islas Baleares', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (415,  4, 415, '', 0, 'La Rioja', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (416,  4, 416, '', 0, 'Comunidad de Madrid', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (417,  4, 417, '', 0, 'Región de Murcia', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (418,  4, 418, '', 0, 'Principado de Asturias', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (419,  4, 419, '', 0, 'Pais Vasco', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (420,  4, 420, '', 0, 'Otros', 1);

-- Regions Germany (id country=5) 
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (501,  5, 501, '', 0, 'Deutschland', 1); 

-- Regions Greece (id_country=102)
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (10201, 10201, 102, NULL, NULL, 'Αττική', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (10202, 10202, 102, NULL, NULL, 'Στερεά Ελλάδα', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (10203, 10203, 102, NULL, NULL, 'Κεντρική Μακεδονία', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (10204, 10204, 102, NULL, NULL, 'Κρήτη', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (10205, 10205, 102, NULL, NULL, 'Ανατολική Μακεδονία και Θράκη', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (10206, 10206, 102, NULL, NULL, 'Ήπειρος', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (10207, 10207, 102, NULL, NULL, 'Ιόνια νησιά', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (10208, 10208, 102, NULL, NULL, 'Βόρειο Αιγαίο', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (10209, 10209, 102, NULL, NULL, 'Πελοπόννησος', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (10210, 10210, 102, NULL, NULL, 'Νότιο Αιγαίο', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (10211, 10211, 102, NULL, NULL, 'Δυτική Ελλάδα', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (10212, 10212, 102, NULL, NULL, 'Θεσσαλία', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (10213, 10213, 102, NULL, NULL, 'Δυτική Μακεδονία', 1);

-- Regions Switzerland (id country=6) 
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (601,  6, 601, '', 1, 'Cantons', 1); 

-- Regions England (id_country=7)
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (701,  7, 701, '', 0, 'England', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (702,  7, 702, '', 0, 'Wales', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (703,  7, 703, '', 0, 'Scotland', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (704,  7, 704, '', 0, 'Northern Ireland', 1);

-- Regions Tunisia (id country=10)
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

-- Region US (id country=11)
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (1101, 11, 1101, '', 0, 'United-States', 1);

-- Region Canada (id country=14)
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (1401, 14, 1401, '', 0, 'Canada', 1);

-- Regions The Netherlands (id country=17)
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (1701, 17, 1701, '', 0,'Provincies van Nederland ', 1);

-- Regions Argentina (id country=23)
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (2301, 23, 2301, '', 0, 'Norte', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (2302, 23, 2302, '', 0, 'Litoral', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (2303, 23, 2303, '', 0, 'Cuyana', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (2304, 23, 2304, '', 0, 'Central', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (2305, 23, 2305, '', 0, 'Patagonia', 1);

-- Regions Australia (id country=28)
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (2801, 28, 2801, '', 0, 'Australia', 1);

-- Regions Brazil (id country=56)
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (5601, 56, 5601, '', 0, 'Brasil', 1);

-- Regions Colombie (id country=70)
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (7001,  70, 7001, '', 0, 'Colombie', 1);

-- Regions Chile (id country=67)
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6701, 6701, 67, NULL, NULL, 'Tarapacá', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6702, 6702, 67, NULL, NULL, 'Antofagasta', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6703, 6703, 67, NULL, NULL, 'Atacama', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6704, 6704, 67, NULL, NULL, 'Coquimbo', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6705, 6705, 67, NULL, NULL, 'Valparaíso', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6706, 6706, 67, NULL, NULL, 'General Bernardo O Higgins', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6707, 6707, 67, NULL, NULL, 'Maule', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6708, 6708, 67, NULL, NULL, 'Biobío', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6709, 6709, 67, NULL, NULL, 'Raucanía', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6710, 6710, 67, NULL, NULL, 'Los Lagos', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6711, 6711, 67, NULL, NULL, 'Aysén General Carlos Ibáñez del Campo', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6712, 6712, 67, NULL, NULL, 'Magallanes y Antártica Chilena', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6713, 6713, 67, NULL, NULL, 'Metropolitana de Santiago', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6714, 6714, 67, NULL, NULL, 'Los Ríos', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6715, 6715, 67, NULL, NULL, 'Arica y Parinacota', 1);

-- Regions San Salvador (id country=86)
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (8601, 8601, 86, NULL, NULL, 'Central', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (8602, 8602, 86, NULL, NULL, 'Oriental', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (8603, 8603, 86, NULL, NULL, 'Occidental', 1);

-- Regions Honduras (id country=114)
insert into llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) values (11401,  114, 11401, '', 0, 'Honduras', 1);

-- Regions India (id country=117)
insert into llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) values (11701,  117, 11701, '', 0, 'India', 1);

-- Regions Mauritius (id country=152)
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (15201,  152, 15201, '', 0, 'Rivière Noire', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (15202,  152, 15202, '', 0, 'Flacq', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (15203,  152, 15203, '', 0, 'Grand Port', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (15204,  152, 15204, '', 0, 'Moka', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (15205,  152, 15205, '', 0, 'Pamplemousses', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (15206,  152, 15206, '', 0, 'Plaines Wilhems', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (15207,  152, 15207, '', 0, 'Port-Louis', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (15208,  152, 15208, '', 0, 'Rivière du Rempart', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (15209,  152, 15209, '', 0, 'Savanne', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (15210,  152, 15210, '', 0, 'Rodrigues', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (15211,  152, 15211, '', 0, 'Les îles Agaléga', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (15212,  152, 15212, '', 0, 'Les écueils des Cargados Carajos', 1);

-- Regions Mexique (id country=154)
insert into llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) values (15401,  154, 15401, '', 0, 'Mexique', 1);

-- Regions Barbados (id country=46)
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (4601,  46, 4601, '', 0, 'Barbados', 1);

-- Regions Venezuela (id country=232)
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (23201,  232, 23201, '', 0, 'Los Andes', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (23202,  232, 23202, '', 0, 'Capital', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (23203,  232, 23203, '', 0, 'Central', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (23204,  232, 23204, '', 0, 'Cento Occidental', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (23205,  232, 23205, '', 0, 'Guayana', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (23206,  232, 23206, '', 0, 'Insular', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (23207,  232, 23207, '', 0, 'Los Llanos', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (23208,  232, 23208, '', 0, 'Nor-Oriental', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (23209,  232, 23209, '', 0, 'Zuliana', 1);
