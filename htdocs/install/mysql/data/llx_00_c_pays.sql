-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
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
-- Do not add comment at end of line. This file is parsed by install and -- are removed

--
-- Countries
--

-- delete from llx_c_pays;
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (0,'',NULL,'-',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (1,'FR','FRA','France',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (2,'BE','BEL','Belgium',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (3,'IT','ITA','Italy',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (4,'ES','ESP','Spain',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (5,'DE','DEU','Germany',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (6,'CH','CHE','Switzerland',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (7,'GB','GBR','United Kingdom',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (8,'IE','IRL','Irland',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (9,'CN','CHN','China',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (10,'TN','TUN','Tunisia',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (11,'US','USA','United States',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (12,'MA','MAR','Maroc',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (13,'DZ','DZA','Algeria',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (14,'CA','CAN','Canada',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (15,'TG','TGO','Togo',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (16,'GA','GAB','Gabon',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (17,'NL','NLD','Nerderland',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (18,'HU','HUN','Hongrie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (19,'RU','RUS','Russia',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (20,'SE','SWE','Sweden',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (21,'CI','CIV','Côte d''Ivoire',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (22,'SN','SEN','Senegal',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (23,'AR','ARG','Argentine',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (24,'CM','CMR','Cameroun',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (25,'PT','PRT','Portugal',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (26,'SA','SAU','Saudi Arabia',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (27,'MC','MCO','Monaco',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (28,'AU','AUS','Australia',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (29,'SG','SGP','Singapour',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (30,'AF','AFG','Afghanistan',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (31,'AX','ALA','Iles Aland',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (32,'AL','ALB','Albanie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (33,'AS','ASM','Samoa américaines',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (34,'AD','AND','Andorre',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (35,'AO','AGO','Angola',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (36,'AI','AIA','Anguilla',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (37,'AQ','ATA','Antarctique',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (38,'AG','ATG','Antigua-et-Barbuda',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (39,'AM','ARM','Arménie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (40,'AW','ABW','Aruba',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (41,'AT','AUT','Autriche',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (42,'AZ','AZE','Azerbaïdjan',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (43,'BS','BHS','Bahamas',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (44,'BH','BHR','Bahreïn',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (45,'BD','BGD','Bangladesh',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (46,'BB','BRB','Barbade',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (47,'BY','BLR','Biélorussie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (48,'BZ','BLZ','Belize',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (49,'BJ','BEN','Bénin',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (50,'BM','BMU','Bermudes',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (51,'BT','BTN','Bhoutan',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (52,'BO','BOL','Bolivie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (53,'BA','BIH','Bosnie-Herzégovine',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (54,'BW','BWA','Botswana',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (55,'BV','BVT','Ile Bouvet',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (56,'BR','BRA','Brazil',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (57,'IO','IOT','Territoire britannique de l''Océan Indien',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (58,'BN','BRN','Brunei',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (59,'BG','BGR','Bulgarie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (60,'BF','BFA','Burkina Faso',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (61,'BI','BDI','Burundi',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (62,'KH','KHM','Cambodge',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (63,'CV','CPV','Cap-Vert',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (64,'KY','CYM','Iles Cayman',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (65,'CF','CAF','République centrafricaine',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (66,'TD','TCD','Tchad',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (67,'CL','CHL','Chili',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (68,'CX','CXR','Ile Christmas',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (69,'CC','CCK','Iles des Cocos (Keeling)',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (70,'CO','COL','Colombie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (71,'KM','COM','Comores',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (72,'CG','COG','Congo',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (73,'CD','COD','République démocratique du Congo',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (74,'CK','COK','Iles Cook',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (75,'CR','CRI','Costa Rica',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (76,'HR','HRV','Croatie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (77,'CU','CUB','Cuba',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (78,'CY','CYP','Chypre',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (79,'CZ','CZE','République Tchèque',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (80,'DK','DNK','Danemark',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (81,'DJ','DJI','Djibouti',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (82,'DM','DMA','Dominique',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (83,'DO','DOM','République Dominicaine',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (84,'EC','ECU','Equateur',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (85,'EG','EGY','Egypte',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (86,'SV','SLV','Salvador',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (87,'GQ','GNQ','Guinée Equatoriale',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (88,'ER','ERI','Erythrée',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (89,'EE','EST','Estonia',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (90,'ET','ETH','Ethiopie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (91,'FK','FLK','Iles Falkland',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (92,'FO','FRO','Iles Féroé',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (93,'FJ','FJI','Iles Fidji',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (94,'FI','FIN','Finlande',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (95,'GF','GUF','Guyane française',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (96,'PF','PYF','Polynésie française',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (97,'TF','ATF','Terres australes françaises',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (98,'GM','GMB','Gambie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (99,'GE','GEO','Georgia',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (100,'GH','GHA','Ghana',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (101,'GI','GIB','Gibraltar',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (102,'GR','GRC','Greece',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (103,'GL','GRL','Groenland',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (104,'GD','GRD','Grenade',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (106,'GU','GUM','Guam',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (107,'GT','GTM','Guatemala',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (108,'GN','GIN','Guinea',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (109,'GW','GNB','Guinea-Bissao',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (111,'HT','HTI','Haiti',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (112,'HM','HMD','Iles Heard et McDonald',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (113,'VA','VAT','Saint-Siège (Vatican)',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (114,'HN','HND','Honduras',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (115,'HK','HKG','Hong Kong',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (116,'IS','ISL','Islande',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (117,'IN','IND','India',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (118,'ID','IDN','Indonésie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (119,'IR','IRN','Iran',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (120,'IQ','IRQ','Iraq',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (121,'IL','ISR','Israel',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (122,'JM','JAM','Jamaïque',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (123,'JP','JPN','Japon',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (124,'JO','JOR','Jordanie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (125,'KZ','KAZ','Kazakhstan',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (126,'KE','KEN','Kenya',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (127,'KI','KIR','Kiribati',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (128,'KP','PRK','North Corea',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (129,'KR','KOR','South Corea',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (130,'KW','KWT','Koweït',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (131,'KG','KGZ','Kirghizistan',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (132,'LA','LAO','Laos',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (133,'LV','LVA','Lettonie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (134,'LB','LBN','Liban',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (135,'LS','LSO','Lesotho',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (136,'LR','LBR','Liberia',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (137,'LY','LBY','Libye',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (138,'LI','LIE','Liechtenstein',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (139,'LT','LTU','Lituanie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (140,'LU','LUX','Luxembourg',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (141,'MO','MAC','Macao',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (142,'MK','MKD','ex-République yougoslave de Macédoine',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (143,'MG','MDG','Madagascar',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (144,'MW','MWI','Malawi',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (145,'MY','MYS','Malaisie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (146,'MV','MDV','Maldives',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (147,'ML','MLI','Mali',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (148,'MT','MLT','Malte',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (149,'MH','MHL','Iles Marshall',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (151,'MR','MRT','Mauritanie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (152,'MU','MUS','Maurice',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (153,'YT','MYT','Mayotte',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (154,'MX','MEX','Mexique',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (155,'FM','FSM','Micronésie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (156,'MD','MDA','Moldavie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (157,'MN','MNG','Mongolie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (158,'MS','MSR','Monserrat',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (159,'MZ','MOZ','Mozambique',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (160,'MM','MMR','Birmanie (Myanmar)',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (161,'NA','NAM','Namibie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (162,'NR','NRU','Nauru',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (163,'NP','NPL','Népal',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (164,'AN',NULL,'Antilles néerlandaises',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (165,'NC','NCL','Nouvelle-Calédonie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (166,'NZ','NZL','Nouvelle-Zélande',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (167,'NI','NIC','Nicaragua',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (168,'NE','NER','Niger',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (169,'NG','NGA','Nigeria',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (170,'NU','NIU','Nioué',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (171,'NF','NFK','Ile Norfolk',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (172,'MP','MNP','Mariannes du Nord',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (173,'NO','NOR','Norvège',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (174,'OM','OMN','Oman',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (175,'PK','PAK','Pakistan',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (176,'PW','PLW','Palaos',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (177,'PS','PSE','Territoire Palestinien Occupé',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (178,'PA','PAN','Panama',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (179,'PG','PNG','Papouasie-Nouvelle-Guinée',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (180,'PY','PRY','Paraguay',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (181,'PE','PER','Peru',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (182,'PH','PHL','Philippines',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (183,'PN','PCN','Iles Pitcairn',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (184,'PL','POL','Pologne',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (185,'PR','PRI','Porto Rico',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (186,'QA','QAT','Qatar',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (188,'RO','ROU','Roumanie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (189,'RW','RWA','Rwanda',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (190,'SH','SHN','Sainte-Hélène',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (191,'KN','KNA','Saint-Christophe-et-Niévès',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (192,'LC','LCA','Sainte-Lucie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (193,'PM','SPM','Saint-Pierre-et-Miquelon',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (194,'VC','VCT','Saint-Vincent-et-les-Grenadines',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (195,'WS','WSM','Samoa',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (196,'SM','SMR','Saint-Marin',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (197,'ST','STP','Sao Tomé-et-Principe',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (198,'RS','SRB','Serbie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (199,'SC','SYC','Seychelles',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (200,'SL','SLE','Sierra Leone',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (201,'SK','SVK','Slovaquie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (202,'SI','SVN','Slovénie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (203,'SB','SLB','Iles Salomon',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (204,'SO','SOM','Somalie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (205,'ZA','ZAF','Afrique du Sud',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (206,'GS','SGS','Iles Géorgie du Sud et Sandwich du Sud',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (207,'LK','LKA','Sri Lanka',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (208,'SD','SDN','Soudan',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (209,'SR','SUR','Suriname',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (210,'SJ','SJM','Iles Svalbard et Jan Mayen',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (211,'SZ','SWZ','Swaziland',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (212,'SY','SYR','Syrie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (213,'TW','TWN','Taïwan',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (214,'TJ','TJK','Tadjikistan',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (215,'TZ','TZA','Tanzanie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (216,'TH','THA','Thaïlande',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (217,'TL','TLS','Timor Oriental',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (218,'TK','TKL','Tokélaou',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (219,'TO','TON','Tonga',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (220,'TT','TTO','Trinité-et-Tobago',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (221,'TR','TUR','Turquie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (222,'TM','TKM','Turkménistan',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (223,'TC','TCA','Iles Turks-et-Caicos',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (224,'TV','TUV','Tuvalu',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (225,'UG','UGA','Ouganda',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (226,'UA','UKR','Ukraine',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (227,'AE','ARE','Émirats arabes unis',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (228,'UM','UMI','Iles mineures éloignées des États-Unis',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (229,'UY','URY','Uruguay',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (230,'UZ','UZB','Ouzbékistan',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (231,'VU','VUT','Vanuatu',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (232,'VE','VEN','Vénézuela',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (233,'VN','VNM','Viêt Nam',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (234,'VG','VGB','Iles Vierges britanniques',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (235,'VI','VIR','Iles Vierges américaines',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (236,'WF','WLF','Wallis-et-Futuna',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (237,'EH','ESH','Sahara occidental',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (238,'YE','YEM','Yémen',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (239,'ZM','ZMB','Zambie',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (240,'ZW','ZWE','Zimbabwe',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (241,'GG','GGY','Guernesey',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (242,'IM','IMN','Ile de Man',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (243,'JE','JEY','Jersey',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (244,'ME','MNE','Monténégro',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (245,'BL','BLM','Saint-Barthélemy',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (246,'MF','MAF','Saint-Martin',1);
INSERT INTO llx_c_pays (rowid,code,code_iso,libelle,active) VALUES (247,'BU', null, 'Burundi',1);
