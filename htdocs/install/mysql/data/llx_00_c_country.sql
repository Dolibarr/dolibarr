-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
-- Copyright (C) 2014 	   Alexandre Spangaro   <aspangaro@open-dsi.fr>
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
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
-- Do not add comment at end of line. This file is parsed by install and -- are removed

--
-- Countries
--

-- delete from llx_c_country;
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (0,'',NULL,'-',1,1);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (1,'FR','FRA','France',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (2,'BE','BEL','Belgium',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (3,'IT','ITA','Italy',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (4,'ES','ESP','Spain',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (5,'DE','DEU','Germany',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (6,'CH','CHE','Switzerland',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (7,'GB','GBR','United Kingdom',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (8,'IE','IRL','Irland',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (9,'CN','CHN','China',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (10,'TN','TUN','Tunisia',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (11,'US','USA','United States',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (12,'MA','MAR','Maroc',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (13,'DZ','DZA','Algeria',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (14,'CA','CAN','Canada',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (15,'TG','TGO','Togo',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (16,'GA','GAB','Gabon',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (17,'NL','NLD','Nederland',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (18,'HU','HUN','Hongrie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (19,'RU','RUS','Russia',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (20,'SE','SWE','Sweden',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (21,'CI','CIV','Côte d''Ivoire',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (22,'SN','SEN','Senegal',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (23,'AR','ARG','Argentine',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (24,'CM','CMR','Cameroun',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (25,'PT','PRT','Portugal',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (26,'SA','SAU','Saudi Arabia',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (27,'MC','MCO','Monaco',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (28,'AU','AUS','Australia',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (29,'SG','SGP','Singapour',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (30,'AF','AFG','Afghanistan',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (31,'AX','ALA','Iles Aland',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (32,'AL','ALB','Albanie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (33,'AS','ASM','Samoa américaines',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (34,'AD','AND','Andorre',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (35,'AO','AGO','Angola',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (36,'AI','AIA','Anguilla',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (37,'AQ','ATA','Antarctique',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (38,'AG','ATG','Antigua-et-Barbuda',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (39,'AM','ARM','Arménie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (41,'AT','AUT','Autriche',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (42,'AZ','AZE','Azerbaïdjan',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (43,'BS','BHS','Bahamas',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (44,'BH','BHR','Bahreïn',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (45,'BD','BGD','Bangladesh',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (46,'BB','BRB','Barbade',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (47,'BY','BLR','Biélorussie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (48,'BZ','BLZ','Belize',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (49,'BJ','BEN','Bénin',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (50,'BM','BMU','Bermudes',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (51,'BT','BTN','Bhoutan',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (52,'BO','BOL','Bolivie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (53,'BA','BIH','Bosnie-Herzégovine',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (54,'BW','BWA','Botswana',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (55,'BV','BVT','Ile Bouvet',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (56,'BR','BRA','Brazil',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (57,'IO','IOT','Territoire britannique de l''Océan Indien',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (58,'BN','BRN','Brunei',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (59,'BG','BGR','Bulgarie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (60,'BF','BFA','Burkina Faso',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (61,'BI','BDI','Burundi',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (62,'KH','KHM','Cambodge',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (63,'CV','CPV','Cap-Vert',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (64,'KY','CYM','Iles Cayman',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (65,'CF','CAF','République centrafricaine',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (66,'TD','TCD','Tchad',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (67,'CL','CHL','Chili',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (68,'CX','CXR','Ile Christmas',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (69,'CC','CCK','Iles des Cocos (Keeling)',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (70,'CO','COL','Colombie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (71,'KM','COM','Comores',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (72,'CG','COG','Congo',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (73,'CD','COD','République démocratique du Congo',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (74,'CK','COK','Iles Cook',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (75,'CR','CRI','Costa Rica',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (76,'HR','HRV','Croatie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (77,'CU','CUB','Cuba',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (78,'CY','CYP','Cyprus',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (79,'CZ','CZE','République Tchèque',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (80,'DK','DNK','Danemark',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (81,'DJ','DJI','Djibouti',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (82,'DM','DMA','Dominique',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (83,'DO','DOM','République Dominicaine',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (84,'EC','ECU','Equateur',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (85,'EG','EGY','Egypte',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (86,'SV','SLV','Salvador',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (87,'GQ','GNQ','Guinée Equatoriale',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (88,'ER','ERI','Erythrée',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (89,'EE','EST','Estonia',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (90,'ET','ETH','Ethiopie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (91,'FK','FLK','Iles Falkland',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (92,'FO','FRO','Iles Féroé',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (93,'FJ','FJI','Iles Fidji',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (94,'FI','FIN','Finlande',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (95,'GF','GUF','Guyane française',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (96,'PF','PYF','Polynésie française',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (97,'TF','ATF','Terres australes françaises',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (98,'GM','GMB','Gambie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (99,'GE','GEO','Georgia',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (100,'GH','GHA','Ghana',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (101,'GI','GIB','Gibraltar',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (102,'GR','GRC','Greece',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (103,'GL','GRL','Groenland',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (104,'GD','GRD','Grenade',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (106,'GU','GUM','Guam',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (107,'GT','GTM','Guatemala',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (108,'GN','GIN','Guinea',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (109,'GW','GNB','Guinea-Bissao',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (111,'HT','HTI','Haiti',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (112,'HM','HMD','Iles Heard et McDonald',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (113,'VA','VAT','Saint-Siège (Vatican)',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (114,'HN','HND','Honduras',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (115,'HK','HKG','Hong Kong',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (116,'IS','ISL','Islande',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (117,'IN','IND','India',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (118,'ID','IDN','Indonésie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (119,'IR','IRN','Iran',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (120,'IQ','IRQ','Iraq',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (121,'IL','ISR','Israel',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (122,'JM','JAM','Jamaïque',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (123,'JP','JPN','Japon',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (124,'JO','JOR','Jordanie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (125,'KZ','KAZ','Kazakhstan',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (126,'KE','KEN','Kenya',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (127,'KI','KIR','Kiribati',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (128,'KP','PRK','North Corea',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (129,'KR','KOR','South Corea',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (130,'KW','KWT','Koweït',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (131,'KG','KGZ','Kirghizistan',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (132,'LA','LAO','Laos',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (133,'LV','LVA','Lettonie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (134,'LB','LBN','Liban',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (135,'LS','LSO','Lesotho',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (136,'LR','LBR','Liberia',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (137,'LY','LBY','Libye',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (138,'LI','LIE','Liechtenstein',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (139,'LT','LTU','Lituanie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (140,'LU','LUX','Luxembourg',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (141,'MO','MAC','Macao',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (142,'MK','MKD','ex-République yougoslave de Macédoine',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (143,'MG','MDG','Madagascar',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (144,'MW','MWI','Malawi',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (145,'MY','MYS','Malaisie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (146,'MV','MDV','Maldives',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (147,'ML','MLI','Mali',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (148,'MT','MLT','Malte',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (149,'MH','MHL','Iles Marshall',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (151,'MR','MRT','Mauritanie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (152,'MU','MUS','Maurice',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (153,'YT','MYT','Mayotte',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (154,'MX','MEX','Mexique',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (155,'FM','FSM','Micronésie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (156,'MD','MDA','Moldavie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (157,'MN','MNG','Mongolie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (158,'MS','MSR','Monserrat',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (159,'MZ','MOZ','Mozambique',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (160,'MM','MMR','Birmanie (Myanmar)',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (161,'NA','NAM','Namibie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (162,'NR','NRU','Nauru',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (163,'NP','NPL','Népal',1,0);
--INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (164,'AN','AWP','Antilles néerlandaises',1,0);
--The Antilles nederland does not exist anymore as a seperate country since 2010. Aruba, Curaçao and Sint Maarten became seperate countries then:
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (40,'AW','ABW','Aruba',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (300,'CW','CUW','Curaçao',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (301,'SX','SXM','Sint Maarten',1,0);
--End of antilles nederland
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (165,'NC','NCL','Nouvelle-Calédonie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (166,'NZ','NZL','Nouvelle-Zélande',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (167,'NI','NIC','Nicaragua',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (168,'NE','NER','Niger',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (169,'NG','NGA','Nigeria',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (170,'NU','NIU','Nioué',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (171,'NF','NFK','Ile Norfolk',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (172,'MP','MNP','Mariannes du Nord',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (173,'NO','NOR','Norvège',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (174,'OM','OMN','Oman',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (175,'PK','PAK','Pakistan',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (176,'PW','PLW','Palaos',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (177,'PS','PSE','Territoire Palestinien Occupé',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (178,'PA','PAN','Panama',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (179,'PG','PNG','Papouasie-Nouvelle-Guinée',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (180,'PY','PRY','Paraguay',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (181,'PE','PER','Peru',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (182,'PH','PHL','Philippines',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (183,'PN','PCN','Iles Pitcairn',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (184,'PL','POL','Pologne',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (185,'PR','PRI','Porto Rico',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (186,'QA','QAT','Qatar',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (188,'RO','ROU','Roumanie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (189,'RW','RWA','Rwanda',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (190,'SH','SHN','Sainte-Hélène',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (191,'KN','KNA','Saint-Christophe-et-Niévès',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (192,'LC','LCA','Sainte-Lucie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (193,'PM','SPM','Saint-Pierre-et-Miquelon',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (194,'VC','VCT','Saint-Vincent-et-les-Grenadines',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (195,'WS','WSM','Samoa',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (196,'SM','SMR','Saint-Marin',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (197,'ST','STP','Sao Tomé-et-Principe',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (198,'RS','SRB','Serbie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (199,'SC','SYC','Seychelles',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (200,'SL','SLE','Sierra Leone',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (201,'SK','SVK','Slovaquie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (202,'SI','SVN','Slovénie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (203,'SB','SLB','Iles Salomon',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (204,'SO','SOM','Somalie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (205,'ZA','ZAF','South Africa',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (206,'GS','SGS','Iles Géorgie du Sud et Sandwich du Sud',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (207,'LK','LKA','Sri Lanka',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (208,'SD','SDN','Soudan',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (209,'SR','SUR','Suriname',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (210,'SJ','SJM','Iles Svalbard et Jan Mayen',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (211,'SZ','SWZ','Swaziland',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (212,'SY','SYR','Syrie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (213,'TW','TWN','Taïwan',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (214,'TJ','TJK','Tadjikistan',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (215,'TZ','TZA','Tanzanie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (216,'TH','THA','Thaïlande',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (217,'TL','TLS','Timor Oriental',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (218,'TK','TKL','Tokélaou',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (219,'TO','TON','Tonga',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (220,'TT','TTO','Trinité-et-Tobago',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (221,'TR','TUR','Turquie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (222,'TM','TKM','Turkménistan',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (223,'TC','TCA','Iles Turks-et-Caicos',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (224,'TV','TUV','Tuvalu',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (225,'UG','UGA','Ouganda',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (226,'UA','UKR','Ukraine',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (227,'AE','ARE','United Arab Emirates',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (228,'UM','UMI','Iles mineures éloignées des États-Unis',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (229,'UY','URY','Uruguay',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (230,'UZ','UZB','Ouzbékistan',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (231,'VU','VUT','Vanuatu',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (232,'VE','VEN','Vénézuela',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (233,'VN','VNM','Viêt Nam',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (234,'VG','VGB','Iles Vierges britanniques',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (235,'VI','VIR','Iles Vierges américaines',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (236,'WF','WLF','Wallis-et-Futuna',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (237,'EH','ESH','Sahara occidental',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (238,'YE','YEM','Yémen',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (239,'ZM','ZMB','Zambie',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (240,'ZW','ZWE','Zimbabwe',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (241,'GG','GGY','Guernesey',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (242,'IM','IMN','Ile de Man',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (243,'JE','JEY','Jersey',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (244,'ME','MNE','Monténégro',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (245,'BL','BLM','Saint-Barthélemy',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (246,'MF','MAF','Saint-Martin',1,0);
INSERT INTO llx_c_country (rowid,code,code_iso,label,active,favorite) VALUES (247,'XK','XKX','Kosovo',1,0);

