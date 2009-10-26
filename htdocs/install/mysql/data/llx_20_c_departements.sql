-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
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
--

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

--
-- Departements/Cantons/Provinces
--

insert into llx_c_departements (rowid, fk_region, code_departement,cheflieu,tncc,ncc,nom) values (0,0,'0','0',0,'-','-');

-- Departements France
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'01','01053',5,'AIN','Ain');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (22,'02','02408',5,'AISNE','Aisne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (83,'03','03190',5,'ALLIER','Allier');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'04','04070',4,'ALPES-DE-HAUTE-PROVENCE','Alpes-de-Haute-Provence');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'05','05061',4,'HAUTES-ALPES','Hautes-Alpes');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'06','06088',4,'ALPES-MARITIMES','Alpes-Maritimes');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'07','07186',5,'ARDECHE','Ardèche');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (21,'08','08105',4,'ARDENNES','Ardennes');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (73,'09','09122',5,'ARIEGE','Ariège');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (21,'10','10387',5,'AUBE','Aube');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (91,'11','11069',5,'AUDE','Aude');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (73,'12','12202',5,'AVEYRON','Aveyron');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'13','13055',4,'BOUCHES-DU-RHONE','Bouches-du-Rhône');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (25,'14','14118',2,'CALVADOS','Calvados');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (83,'15','15014',2,'CANTAL','Cantal');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (54,'16','16015',3,'CHARENTE','Charente');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (54,'17','17300',3,'CHARENTE-MARITIME','Charente-Maritime');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (24,'18','18033',2,'CHER','Cher');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (74,'19','19272',3,'CORREZE','Corrèze');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (94,'2A','2A004',3,'CORSE-DU-SUD','Corse-du-Sud');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (94,'2B','2B033',3,'HAUTE-CORSE','Haute-Corse');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (26,'21','21231',3,'COTE-D\'OR','Côte-d\'Or');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (53,'22','22278',4,'COTES-D\'ARMOR','Côtes-d\'Armor');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (74,'23','23096',3,'CREUSE','Creuse');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (72,'24','24322',3,'DORDOGNE','Dordogne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (43,'25','25056',2,'DOUBS','Doubs');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'26','26362',3,'DROME','Drôme');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (23,'27','27229',5,'EURE','Eure');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (24,'28','28085',1,'EURE-ET-LOIR','Eure-et-Loir');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (53,'29','29232',2,'FINISTERE','Finistère');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (91,'30','30189',2,'GARD','Gard');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (73,'31','31555',3,'HAUTE-GARONNE','Haute-Garonne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (73,'32','32013',2,'GERS','Gers');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (72,'33','33063',3,'GIRONDE','Gironde');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (91,'34','34172',5,'HERAULT','Hérault');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (53,'35','35238',1,'ILLE-ET-VILAINE','Ille-et-Vilaine');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (24,'36','36044',5,'INDRE','Indre');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (24,'37','37261',1,'INDRE-ET-LOIRE','Indre-et-Loire');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'38','38185',5,'ISERE','Isère');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (43,'39','39300',2,'JURA','Jura');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (72,'40','40192',4,'LANDES','Landes');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (24,'41','41018',0,'LOIR-ET-CHER','Loir-et-Cher');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'42','42218',3,'LOIRE','Loire');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (83,'43','43157',3,'HAUTE-LOIRE','Haute-Loire');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (52,'44','44109',3,'LOIRE-ATLANTIQUE','Loire-Atlantique');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (24,'45','45234',2,'LOIRET','Loiret');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (73,'46','46042',2,'LOT','Lot');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (72,'47','47001',0,'LOT-ET-GARONNE','Lot-et-Garonne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (91,'48','48095',3,'LOZERE','Lozère');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (52,'49','49007',0,'MAINE-ET-LOIRE','Maine-et-Loire');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (25,'50','50502',3,'MANCHE','Manche');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (21,'51','51108',3,'MARNE','Marne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (21,'52','52121',3,'HAUTE-MARNE','Haute-Marne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (52,'53','53130',3,'MAYENNE','Mayenne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (41,'54','54395',0,'MEURTHE-ET-MOSELLE','Meurthe-et-Moselle');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (41,'55','55029',3,'MEUSE','Meuse');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (53,'56','56260',2,'MORBIHAN','Morbihan');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (41,'57','57463',3,'MOSELLE','Moselle');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (26,'58','58194',3,'NIEVRE','Nièvre');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (31,'59','59350',2,'NORD','Nord');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (22,'60','60057',5,'OISE','Oise');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (25,'61','61001',5,'ORNE','Orne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (31,'62','62041',2,'PAS-DE-CALAIS','Pas-de-Calais');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (83,'63','63113',2,'PUY-DE-DOME','Puy-de-Dôme');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (72,'64','64445',4,'PYRENEES-ATLANTIQUES','Pyrénées-Atlantiques');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (73,'65','65440',4,'HAUTES-PYRENEES','Hautes-Pyrénées');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (91,'66','66136',4,'PYRENEES-ORIENTALES','Pyrénées-Orientales');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (42,'67','67482',2,'BAS-RHIN','Bas-Rhin');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (42,'68','68066',2,'HAUT-RHIN','Haut-Rhin');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'69','69123',2,'RHONE','Rhône');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (43,'70','70550',3,'HAUTE-SAONE','Haute-Saône');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (26,'71','71270',0,'SAONE-ET-LOIRE','Saône-et-Loire');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (52,'72','72181',3,'SARTHE','Sarthe');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'73','73065',3,'SAVOIE','Savoie');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (82,'74','74010',3,'HAUTE-SAVOIE','Haute-Savoie');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'75','75056',0,'PARIS','Paris');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (23,'76','76540',3,'SEINE-MARITIME','Seine-Maritime');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'77','77288',0,'SEINE-ET-MARNE','Seine-et-Marne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'78','78646',4,'YVELINES','Yvelines');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (54,'79','79191',4,'DEUX-SEVRES','Deux-Sèvres');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (22,'80','80021',3,'SOMME','Somme');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (73,'81','81004',2,'TARN','Tarn');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (73,'82','82121',0,'TARN-ET-GARONNE','Tarn-et-Garonne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'83','83137',2,'VAR','Var');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'84','84007',0,'VAUCLUSE','Vaucluse');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (52,'85','85191',3,'VENDEE','Vendée');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (54,'86','86194',3,'VIENNE','Vienne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (74,'87','87085',3,'HAUTE-VIENNE','Haute-Vienne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (41,'88','88160',4,'VOSGES','Vosges');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (26,'89','89024',5,'YONNE','Yonne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (43,'90','90010',0,'TERRITOIRE DE BELFORT','Territoire de Belfort');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'91','91228',5,'ESSONNE','Essonne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'92','92050',4,'HAUTS-DE-SEINE','Hauts-de-Seine');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'93','93008',3,'SEINE-SAINT-DENIS','Seine-Saint-Denis');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'94','94028',2,'VAL-DE-MARNE','Val-de-Marne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'95','95500',2,'VAL-D\'OISE','Val-d\'Oise');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values ( 1,'971','97105',3,'GUADELOUPE','Guadeloupe');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values ( 2,'972','97209',3,'MARTINIQUE','Martinique');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values ( 3,'973','97302',3,'GUYANE','Guyane');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values ( 4,'974','97411',3,'REUNION','Réunion');

-- Provinces Belgium
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (201,'01','',1,'ANVERS','Anvers');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (203,'02','',3,'BRUXELLES-CAPITALE','Bruxelles-Capitale');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (202,'03','',2,'BRABANT-WALLON','Brabant-Wallon');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (201,'04','',1,'BRABANT-FLAMAND','Brabant-Flamand');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (201,'05','',1,'FLANDRE-OCCIDENTALE','Flandre-Occidentale');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (201,'06','',1,'FLANDRE-ORIENTALE','Flandre-Orientale');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (202,'07','',2,'HAINAUT','Hainaut');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (201,'08','',2,'LIEGE','Liège');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (202,'09','',1,'LIMBOURG','Limbourg');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (202,'10','',2,'LUXEMBOURG','Luxembourg');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (201,'11','',2,'NAMUR','Namur');

-- Provinces Australia
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2801,'NSW','',1,'','New South Wales');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2801,'VIC','',1,'','Victoria');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2801,'QLD','',1,'','Queensland');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2801, 'SA','',1,'','South Australia');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2801,'ACT','',1,'','Australia Capital Territory');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2801,'TAS','',1,'','Tasmania');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2801, 'WA','',1,'','Western Australia');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (2801, 'NT','',1,'','Northern Territory');

-- Provinces Spain
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('01', 419, '', 19, 'PAIS VASCO', 'País Vasco', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('02', 404, '', 4, 'ALBACETE', 'Albacete', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('03', 411, '', 11, 'ALICANTE', 'Alicante', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('04', 401, '', 1, 'ALMERIA', 'Almería', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('05', 403, '', 3, 'AVILA', 'Avila', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('06', 412, '', 12, 'BADAJOZ', 'Badajoz', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('07', 414, '', 14, 'ISLAS BALEARES', 'Islas Baleares', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('08', 406, '', 6, 'BARCELONA', 'Barcelona', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('09', 403, '', 8, 'BURGOS', 'Burgos', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('10', 412, '', 12, 'CACERES', 'Cáceres', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('11', 401, '', 1, 'CADIz', 'Cádiz', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('12', 411, '', 11, 'CASTELLON', 'Castellón', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('13', 404, '', 4, 'CIUDAD REAL', 'Ciudad Real', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('14', 401, '', 1, 'CORDOBA', 'Córdoba', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('15', 413, '', 13, 'LA CORUÑA', 'La Coruña', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('16', 404, '', 4, 'CUENCA', 'Cuenca', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('17', 406, '', 6, 'GERONA', 'Gerona', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('18', 401, '', 1, 'GRANADA', 'Granada', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('19', 404, '', 4, 'GUADALAJARA', 'Guadalajara', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('20', 419, '', 19, 'GUIPUZCOA', 'Guipúzcoa', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('21', 401, '', 1, 'HUELVA', 'Huelva', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('22', 402, '', 2, 'HUESCA', 'Huesca', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('23', 401, '', 1, 'JAEN', 'Jaén', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('24', 403, '', 3, 'LEON', 'León', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('25', 406, '', 6, 'LERIDA', 'Lérida', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('26', 415, '', 15, 'LA RIOJA', 'La Rioja', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('27', 413, '', 13, 'LUGO', 'Lugo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('28', 416, '', 16, 'MADRID', 'Madrid', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('29', 401, '', 1, 'MALAGA', 'Málaga', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('30', 417, '', 17, 'MURCIA', 'Murcia', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('31', 408, '', 8, 'NAVARRA', 'Navarra', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('32', 413, '', 13, 'ORENSE', 'Orense', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('33', 418, '', 18, 'ASTURIAS', 'Asturias', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('34', 403, '', 3, 'PALENCIA', 'Palencia', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('35', 405, '', 5, 'LAS PALMAS', 'Las Palmas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('36', 413, '', 13, 'PONTEVEDRA', 'Pontevedra', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('37', 403, '', 3, 'SALAMANCA', 'Salamanca', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('38', 405, '', 5, 'STA. CRUZ DE TENERIFE', 'Sta. Cruz de Tenerife', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('39', 410, '', 10, 'CANTABRIA', 'Cantabria', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('40', 403, '', 3, 'SEGOVIA', 'Segovia', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('41', 401, '', 1, 'SEVILLA', 'Sevilla', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('42', 403, '', 3, 'SORIA', 'Soria', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('43', 406, '', 6, 'TARRAGONA', 'Tarragona', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('44', 402, '', 2, 'TERUEL', 'Teruel', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('45', 404, '', 5, 'TOLEDO', 'Toledo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('46', 411, '', 11, 'VALENCIA', 'Valencia', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('47', 403, '', 3, 'VALLADOLID', 'Valladolid', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('48', 419, '', 19, 'VIZCAYA', 'Vizcaya', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('49', 403, '', 3, 'ZAMORA', 'Zamora', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('50', 402, '', 1, 'ZARAGOZA', 'Zaragoza', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('51', 407, '', 7, 'CEUTA', 'Ceuta', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('52', 409, '', 9, 'MELILLA', 'Melilla', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('53', 420, '', 20, 'OTROS', 'Otros', 1);

-- Cantons Switzerland 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'AG','ARGOVIE','Argovie',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'AI','APPENZELL RHODES INTERIEURES','Appenzell Rhodes intérieures',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'AR','APPENZELL RHODES EXTERIEURES','Appenzell Rhodes extérieures',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'BE','BERNE','Berne',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'BL','BALE CAMPAGNE','Bâle Campagne',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'BS','BALE VILLE','Bâle Ville',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'FR','FRIBOURG','Fribourg',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'GE','GENEVE','Genève',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'GL','GLARIS','Glaris',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'GR','GRISONS','Grisons',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'JU','JURA','Jura',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'LU','LUCERNE','Lucerne',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'NE','NEUCHATEL','Neuchâtel',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'NW','NIDWALD','Nidwald',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'OW','OBWALD','Obwald',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'SG','SAINT-GALL','Saint-Gall',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'SH','SCHAFFHOUSE','Schaffhouse',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'SO','SOLEURE','Soleure',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'SZ','SCHWYZ','Schwyz',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'TG','THURGOVIE','Thurgovie',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'TI','TESSIN','Tessin',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'UR','URI','Uri',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'VD','VAUD','Vaud',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'VS','VALAIS','Valais',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'ZG','ZUG','Zug',1); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (601,'ZH','ZURICH','Zürich',1);

-- Provinces US
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('AL', 110, '', 0, 'ALABAMA', 'Alabama', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('AK', 110, '', 0, 'ALASKA', 'Alaska', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('AZ', 110, '', 0, 'ARIZONA', 'Arizona', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('AR', 110, '', 0, 'ARKANSAS', 'Arkansas', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('CA', 110, '', 0, 'CALIFORNIA', 'California', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('CO', 110, '', 0, 'COLORADO', 'Colorado', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('CT', 110, '', 0, 'CONNECTICUT', 'Connecticut', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('DE', 110, '', 0, 'DELAWARE', 'Delaware', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('FL', 110, '', 0, 'FLORIDA', 'Florida', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('GA', 110, '', 0, 'GEORGIA', 'Georgia', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('HI', 110, '', 0, 'HAWAII', 'Hawaii', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('ID', 110, '', 0, 'IDAHO', 'Idaho', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('IL', 110, '', 0, 'ILLINOIS','Illinois', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('IN', 110, '', 0, 'INDIANA', 'Indiana', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('IA', 110, '', 0, 'IOWA', 'Iowa', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('KS', 110, '', 0, 'KANSAS', 'Kansas', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('KY', 110, '', 0, 'KENTUCKY', 'Kentucky', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('LA', 110, '', 0, 'LOUISIANA', 'Louisiana', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('ME', 110, '', 0, 'MAINE', 'Maine', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('MD', 110, '', 0, 'MARYLAND', 'Maryland', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('MA', 110, '', 0, 'MASSACHUSSETTS', 'Massachusetts', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('MI', 110, '', 0, 'MICHIGAN', 'Michigan', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('MN', 110, '', 0, 'MINNESOTA', 'Minnesota', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('MS', 110, '', 0, 'MISSISSIPPI', 'Mississippi', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('MO', 110, '', 0, 'MISSOURI', 'Missouri', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('MT', 110, '', 0, 'MONTANA', 'Montana', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('NE', 110, '', 0, 'NEBRASKA', 'Nebraska', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('NV', 110, '', 0, 'NEVADA', 'Nevada', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('NH', 110, '', 0, 'NEW HAMPSHIRE', 'New Hampshire', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('NJ', 110, '', 0, 'NEW JERSEY', 'New Jersey', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('NM', 110, '', 0, 'NEW MEXICO', 'New Mexico', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('NY', 110, '', 0, 'NEW YORK', 'New York', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('NC', 110, '', 0, 'NORTH CAROLINA', 'North Carolina', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('ND', 110, '', 0, 'NORTH DAKOTA', 'North Dakota', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('OH', 110, '', 0, 'OHIO', 'Ohio', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('OK', 110, '', 0, 'OKLAHOMA', 'Oklahoma', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('OR', 110, '', 0, 'OREGON', 'Oregon', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('PA', 110, '', 0, 'PENNSYLVANIA', 'Pennsylvania', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('RI', 110, '', 0, 'RHODE ISLAND', 'Rhode Island', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('SC', 110, '', 0, 'SOUTH CAROLINA', 'South Carolina', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('SD', 110, '', 0, 'SOUTH DAKOTA', 'South Dakota', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('TN', 110, '', 0, 'TENNESSEE', 'Tennessee', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('TX', 110, '', 0, 'TEXAS', 'Texas', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('UT', 110, '', 0, 'UTAH', 'Utah', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('VT', 110, '', 0, 'VERMONT', 'Vermont', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('VA', 110, '', 0, 'VIRGINIA', 'Virginia', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('WA', 110, '', 0, 'WASHINGTON', 'Washington', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('WV', 110, '', 0, 'WEST VIRGINIA', 'West Virginia', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('WI', 110, '', 0, 'WISCONSIN', 'Wisconsin', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('WY', 110, '', 0, 'WYOMING', 'Wyoming', 1);
