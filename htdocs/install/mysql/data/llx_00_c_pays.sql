-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
--

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

--
-- Pays
--

-- delete from llx_c_pays;
insert into llx_c_pays (rowid,code,libelle) values (0,  ''  , '-'              );
insert into llx_c_pays (rowid,code,libelle) values (1,  'FR', 'France'         );
insert into llx_c_pays (rowid,code,libelle) values (2,  'BE', 'Belgium'        );
insert into llx_c_pays (rowid,code,libelle) values (3,  'IT', 'Italy'          );
insert into llx_c_pays (rowid,code,libelle) values (4,  'ES', 'Spain'          );
insert into llx_c_pays (rowid,code,libelle) values (5,  'DE', 'Germany'        );
insert into llx_c_pays (rowid,code,libelle) values (6,  'CH', 'Suisse'         );
insert into llx_c_pays (rowid,code,libelle) values (7,  'GB', 'United Kingdom' );
insert into llx_c_pays (rowid,code,libelle) values (8,  'IE', 'Irland'         );
insert into llx_c_pays (rowid,code,libelle) values (9,  'CN', 'China'          );
insert into llx_c_pays (rowid,code,libelle) values (10, 'TN', 'Tunisie'        );
insert into llx_c_pays (rowid,code,libelle) values (11, 'US', 'United States'  );
insert into llx_c_pays (rowid,code,libelle) values (12, 'MA', 'Maroc'          );
insert into llx_c_pays (rowid,code,libelle) values (13, 'DZ', 'Algérie'        );
insert into llx_c_pays (rowid,code,libelle) values (14, 'CA', 'Canada'         );
insert into llx_c_pays (rowid,code,libelle) values (15, 'TG', 'Togo'           );
insert into llx_c_pays (rowid,code,libelle) values (16, 'GA', 'Gabon'          );
insert into llx_c_pays (rowid,code,libelle) values (17, 'NL', 'Nerderland'     );
insert into llx_c_pays (rowid,code,libelle) values (18, 'HU', 'Hongrie'        );
insert into llx_c_pays (rowid,code,libelle) values (19, 'RU', 'Russia'         );
insert into llx_c_pays (rowid,code,libelle) values (20, 'SE', 'Sweden'         );
insert into llx_c_pays (rowid,code,libelle) values (21, 'CI', 'Côte d''Ivoire' );
insert into llx_c_pays (rowid,code,libelle) values (22, 'SN', 'Sénégal'        );
insert into llx_c_pays (rowid,code,libelle) values (23, 'AR', 'Argentine'      );
insert into llx_c_pays (rowid,code,libelle) values (24, 'CM', 'Cameroun'       );
insert into llx_c_pays (rowid,code,libelle) values (25, 'PT', 'Portugal'       );
insert into llx_c_pays (rowid,code,libelle) values (26, 'SA', 'Arabie Saoudite');
insert into llx_c_pays (rowid,code,libelle) values (27, 'MC', 'Monaco'         );
insert into llx_c_pays (rowid,code,libelle) values (28, 'AU', 'Australia'      );
insert into llx_c_pays (rowid,code,libelle) values (29, 'SG', 'Singapour'     );
insert into llx_c_pays (rowid,code,libelle) values (30, 'AF', 'Afghanistan'    );
insert into llx_c_pays (rowid,code,libelle) values (31, 'AX', 'Iles Aland'     );
insert into llx_c_pays (rowid,code,libelle) values (32, 'AL', 'Albanie'        );
insert into llx_c_pays (rowid,code,libelle) values (33, 'AS', 'Samoa américaines');
insert into llx_c_pays (rowid,code,libelle) values (34, 'AD', 'Andorre'        );
insert into llx_c_pays (rowid,code,libelle) values (35, 'AO', 'Angola'         );
insert into llx_c_pays (rowid,code,libelle) values (36, 'AI', 'Anguilla'       );
insert into llx_c_pays (rowid,code,libelle) values (37, 'AQ', 'Antarctique'    );
insert into llx_c_pays (rowid,code,libelle) values (38, 'AG', 'Antigua-et-Barbuda');
insert into llx_c_pays (rowid,code,libelle) values (39, 'AM', 'Arménie'        );
insert into llx_c_pays (rowid,code,libelle) values (40, 'AW', 'Aruba'          );
insert into llx_c_pays (rowid,code,libelle) values (41, 'AT', 'Autriche'       );
insert into llx_c_pays (rowid,code,libelle) values (42, 'AZ', 'Azerbaïdjan'    );
insert into llx_c_pays (rowid,code,libelle) values (43, 'BS', 'Bahamas'        );
insert into llx_c_pays (rowid,code,libelle) values (44, 'BH', 'Bahreïn'        );
insert into llx_c_pays (rowid,code,libelle) values (45, 'BD', 'Bangladesh'     );
insert into llx_c_pays (rowid,code,libelle) values (46, 'BB', 'Barbade'        );
insert into llx_c_pays (rowid,code,libelle) values (47, 'BY', 'Biélorussie'    );
insert into llx_c_pays (rowid,code,libelle) values (48, 'BZ', 'Belize'         );
insert into llx_c_pays (rowid,code,libelle) values (49, 'BJ', 'Bénin'          );
insert into llx_c_pays (rowid,code,libelle) values (50, 'BM', 'Bermudes'       );
insert into llx_c_pays (rowid,code,libelle) values (51, 'BT', 'Bhoutan'        );
insert into llx_c_pays (rowid,code,libelle) values (52, 'BO', 'Bolivie'        );
insert into llx_c_pays (rowid,code,libelle) values (53, 'BA', 'Bosnie-Herzégovine');
insert into llx_c_pays (rowid,code,libelle) values (54, 'BW', 'Botswana'       );
insert into llx_c_pays (rowid,code,libelle) values (55, 'BV', 'Ile Bouvet'     );
insert into llx_c_pays (rowid,code,libelle) values (56, 'BR', 'Brazil'         );
insert into llx_c_pays (rowid,code,libelle) values (57, 'IO', 'Territoire britannique de l''Océan Indien');
insert into llx_c_pays (rowid,code,libelle) values (58, 'BN', 'Brunei'         );
insert into llx_c_pays (rowid,code,libelle) values (59, 'BG', 'Bulgarie'       );
insert into llx_c_pays (rowid,code,libelle) values (60, 'BF', 'Burkina Faso'   );
insert into llx_c_pays (rowid,code,libelle) values (61, 'BI', 'Burundi'        );
insert into llx_c_pays (rowid,code,libelle) values (62, 'KH', 'Cambodge'       );
insert into llx_c_pays (rowid,code,libelle) values (63, 'CV', 'Cap-Vert'       );
insert into llx_c_pays (rowid,code,libelle) values (64, 'KY', 'Iles Cayman'    );
insert into llx_c_pays (rowid,code,libelle) values (65, 'CF', 'République centrafricaine');
insert into llx_c_pays (rowid,code,libelle) values (66, 'TD', 'Tchad'          );
insert into llx_c_pays (rowid,code,libelle) values (67, 'CL', 'Chili'          );
insert into llx_c_pays (rowid,code,libelle) values (68, 'CX', 'Ile Christmas'  );
insert into llx_c_pays (rowid,code,libelle) values (69, 'CC', 'Iles des Cocos (Keeling)');
insert into llx_c_pays (rowid,code,libelle) values (70, 'CO', 'Colombie'       );
insert into llx_c_pays (rowid,code,libelle) values (71, 'KM', 'Comores'        );
insert into llx_c_pays (rowid,code,libelle) values (72, 'CG', 'Congo'          );
insert into llx_c_pays (rowid,code,libelle) values (73, 'CD', 'République démocratique du Congo');
insert into llx_c_pays (rowid,code,libelle) values (74, 'CK', 'Iles Cook'      );
insert into llx_c_pays (rowid,code,libelle) values (75, 'CR', 'Costa Rica'     );
insert into llx_c_pays (rowid,code,libelle) values (76, 'HR', 'Croatie'        );
insert into llx_c_pays (rowid,code,libelle) values (77, 'CU', 'Cuba'           );
insert into llx_c_pays (rowid,code,libelle) values (78, 'CY', 'Chypre'         );
insert into llx_c_pays (rowid,code,libelle) values (79, 'CZ', 'République Tchèque');
insert into llx_c_pays (rowid,code,libelle) values (80, 'DK', 'Danemark'       );
insert into llx_c_pays (rowid,code,libelle) values (81, 'DJ', 'Djibouti'       );
insert into llx_c_pays (rowid,code,libelle) values (82, 'DM', 'Dominique'      );
insert into llx_c_pays (rowid,code,libelle) values (83, 'DO', 'République Dominicaine');
insert into llx_c_pays (rowid,code,libelle) values (84, 'EC', 'Equateur'       );
insert into llx_c_pays (rowid,code,libelle) values (85, 'EG', 'Egypte'         );
insert into llx_c_pays (rowid,code,libelle) values (86, 'SV', 'Salvador'       );
insert into llx_c_pays (rowid,code,libelle) values (87, 'GQ', 'Guinée Equatoriale');
insert into llx_c_pays (rowid,code,libelle) values (88, 'ER', 'Erythrée'       );
insert into llx_c_pays (rowid,code,libelle) values (89, 'EE', 'Estonie'        );
insert into llx_c_pays (rowid,code,libelle) values (90, 'ET', 'Ethiopie'       );
insert into llx_c_pays (rowid,code,libelle) values (91, 'FK', 'Iles Falkland'  );
insert into llx_c_pays (rowid,code,libelle) values (92, 'FO', 'Iles Féroé'     );
insert into llx_c_pays (rowid,code,libelle) values (93, 'FJ', 'Iles Fidji'     );
insert into llx_c_pays (rowid,code,libelle) values (94, 'FI', 'Finlande'       );
insert into llx_c_pays (rowid,code,libelle) values (95, 'GF', 'Guyane française');
insert into llx_c_pays (rowid,code,libelle) values (96, 'PF', 'Polynésie française');
insert into llx_c_pays (rowid,code,libelle) values (97, 'TF', 'Terres australes françaises');
insert into llx_c_pays (rowid,code,libelle) values (98, 'GM', 'Gambie'         );
insert into llx_c_pays (rowid,code,libelle) values (99, 'GE', 'Géorgie'       );
insert into llx_c_pays (rowid,code,libelle) values (100, 'GH', 'Ghana'         );
insert into llx_c_pays (rowid,code,libelle) values (101, 'GI', 'Gibraltar'     );
insert into llx_c_pays (rowid,code,libelle) values (102, 'GR', 'Grèce'         );
insert into llx_c_pays (rowid,code,libelle) values (103, 'GL', 'Groenland'     );
insert into llx_c_pays (rowid,code,libelle) values (104, 'GD', 'Grenade'       );
--insert into llx_c_pays (rowid,code,libelle) values (105, 'GP', 'Guadeloupe'    );
insert into llx_c_pays (rowid,code,libelle) values (106, 'GU', 'Guam'          );
insert into llx_c_pays (rowid,code,libelle) values (107, 'GT', 'Guatemala'     );
insert into llx_c_pays (rowid,code,libelle) values (108, 'GN', 'Guinée'        );
insert into llx_c_pays (rowid,code,libelle) values (109, 'GW', 'Guinée-Bissao' );
--insert into llx_c_pays (rowid,code,libelle) values (110, 'GY', 'Guyana'        );
insert into llx_c_pays (rowid,code,libelle) values (111, 'HT', 'Haiti'         );
insert into llx_c_pays (rowid,code,libelle) values (112, 'HM', 'Iles Heard et McDonald');
insert into llx_c_pays (rowid,code,libelle) values (113, 'VA', 'Saint-Siège (Vatican)');
insert into llx_c_pays (rowid,code,libelle) values (114, 'HN', 'Honduras'      );
insert into llx_c_pays (rowid,code,libelle) values (115, 'HK', 'Hong Kong'     );
insert into llx_c_pays (rowid,code,libelle) values (116, 'IS', 'Islande'       );
insert into llx_c_pays (rowid,code,libelle) values (117, 'IN', 'India'          );
insert into llx_c_pays (rowid,code,libelle) values (118, 'ID', 'Indonésie'     );
insert into llx_c_pays (rowid,code,libelle) values (119, 'IR', 'Iran'          );
insert into llx_c_pays (rowid,code,libelle) values (120, 'IQ', 'Iraq'          );
insert into llx_c_pays (rowid,code,libelle) values (121, 'IL', 'Israel'        );
insert into llx_c_pays (rowid,code,libelle) values (122, 'JM', 'Jamaïque'      );
insert into llx_c_pays (rowid,code,libelle) values (123, 'JP', 'Japon'         );
insert into llx_c_pays (rowid,code,libelle) values (124, 'JO', 'Jordanie'      );
insert into llx_c_pays (rowid,code,libelle) values (125, 'KZ', 'Kazakhstan'    );
insert into llx_c_pays (rowid,code,libelle) values (126, 'KE', 'Kenya'         );
insert into llx_c_pays (rowid,code,libelle) values (127, 'KI', 'Kiribati'      );
insert into llx_c_pays (rowid,code,libelle) values (128, 'KP', 'Corée du Nord' );
insert into llx_c_pays (rowid,code,libelle) values (129, 'KR', 'Corée du Sud'  );
insert into llx_c_pays (rowid,code,libelle) values (130, 'KW', 'Koweït'        );
insert into llx_c_pays (rowid,code,libelle) values (131, 'KG', 'Kirghizistan'  );
insert into llx_c_pays (rowid,code,libelle) values (132, 'LA', 'Laos'          );
insert into llx_c_pays (rowid,code,libelle) values (133, 'LV', 'Lettonie'      );
insert into llx_c_pays (rowid,code,libelle) values (134, 'LB', 'Liban'         );
insert into llx_c_pays (rowid,code,libelle) values (135, 'LS', 'Lesotho'       );
insert into llx_c_pays (rowid,code,libelle) values (136, 'LR', 'Liberia'       );
insert into llx_c_pays (rowid,code,libelle) values (137, 'LY', 'Libye'         );
insert into llx_c_pays (rowid,code,libelle) values (138, 'LI', 'Liechtenstein' );
insert into llx_c_pays (rowid,code,libelle) values (139, 'LT', 'Lituanie'      );
insert into llx_c_pays (rowid,code,libelle) values (140, 'LU', 'Luxembourg'    );
insert into llx_c_pays (rowid,code,libelle) values (141, 'MO', 'Macao'         );
insert into llx_c_pays (rowid,code,libelle) values (142, 'MK', 'ex-République yougoslave de Macédoine');
insert into llx_c_pays (rowid,code,libelle) values (143, 'MG', 'Madagascar'    );
insert into llx_c_pays (rowid,code,libelle) values (144, 'MW', 'Malawi'        );
insert into llx_c_pays (rowid,code,libelle) values (145, 'MY', 'Malaisie'      );
insert into llx_c_pays (rowid,code,libelle) values (146, 'MV', 'Maldives'      );
insert into llx_c_pays (rowid,code,libelle) values (147, 'ML', 'Mali'          );
insert into llx_c_pays (rowid,code,libelle) values (148, 'MT', 'Malte'         );
insert into llx_c_pays (rowid,code,libelle) values (149, 'MH', 'Iles Marshall' );
--insert into llx_c_pays (rowid,code,libelle) values (150, 'MQ', 'Martinique'    );
insert into llx_c_pays (rowid,code,libelle) values (151, 'MR', 'Mauritanie'    );
insert into llx_c_pays (rowid,code,libelle) values (152, 'MU', 'Maurice'       );
insert into llx_c_pays (rowid,code,libelle) values (153, 'YT', 'Mayotte'       );
insert into llx_c_pays (rowid,code,libelle) values (154, 'MX', 'Mexique'       );
insert into llx_c_pays (rowid,code,libelle) values (155, 'FM', 'Micronésie'    );
insert into llx_c_pays (rowid,code,libelle) values (156, 'MD', 'Moldavie'      );
insert into llx_c_pays (rowid,code,libelle) values (157, 'MN', 'Mongolie'      );
insert into llx_c_pays (rowid,code,libelle) values (158, 'MS', 'Monserrat'     );
insert into llx_c_pays (rowid,code,libelle) values (159, 'MZ', 'Mozambique'    );
insert into llx_c_pays (rowid,code,libelle) values (160, 'MM', 'Birmanie (Myanmar)'      );
insert into llx_c_pays (rowid,code,libelle) values (161, 'NA', 'Namibie'       );
insert into llx_c_pays (rowid,code,libelle) values (162, 'NR', 'Nauru'         );
insert into llx_c_pays (rowid,code,libelle) values (163, 'NP', 'Népal'         );
insert into llx_c_pays (rowid,code,libelle) values (164, 'AN', 'Antilles néerlandaises');
insert into llx_c_pays (rowid,code,libelle) values (165, 'NC', 'Nouvelle-Calédonie');
insert into llx_c_pays (rowid,code,libelle) values (166, 'NZ', 'Nouvelle-Zélande');
insert into llx_c_pays (rowid,code,libelle) values (167, 'NI', 'Nicaragua'     );
insert into llx_c_pays (rowid,code,libelle) values (168, 'NE', 'Niger'         );
insert into llx_c_pays (rowid,code,libelle) values (169, 'NG', 'Nigeria'       );
insert into llx_c_pays (rowid,code,libelle) values (170, 'NU', 'Nioué'         );
insert into llx_c_pays (rowid,code,libelle) values (171, 'NF', 'Ile Norfolk'   );
insert into llx_c_pays (rowid,code,libelle) values (172, 'MP', 'Mariannes du Nord');
insert into llx_c_pays (rowid,code,libelle) values (173, 'NO', 'Norvège'       );
insert into llx_c_pays (rowid,code,libelle) values (174, 'OM', 'Oman'          );
insert into llx_c_pays (rowid,code,libelle) values (175, 'PK', 'Pakistan'      );
insert into llx_c_pays (rowid,code,libelle) values (176, 'PW', 'Palaos'         );
insert into llx_c_pays (rowid,code,libelle) values (177, 'PS', 'Territoire Palestinien Occupé');
insert into llx_c_pays (rowid,code,libelle) values (178, 'PA', 'Panama'        );
insert into llx_c_pays (rowid,code,libelle) values (179, 'PG', 'Papouasie-Nouvelle-Guinée');
insert into llx_c_pays (rowid,code,libelle) values (180, 'PY', 'Paraguay'      );
insert into llx_c_pays (rowid,code,libelle) values (181, 'PE', 'Pérou'         );
insert into llx_c_pays (rowid,code,libelle) values (182, 'PH', 'Philippines'   );
insert into llx_c_pays (rowid,code,libelle) values (183, 'PN', 'Iles Pitcairn' );
insert into llx_c_pays (rowid,code,libelle) values (184, 'PL', 'Pologne'       );
insert into llx_c_pays (rowid,code,libelle) values (185, 'PR', 'Porto Rico'    );
insert into llx_c_pays (rowid,code,libelle) values (186, 'QA', 'Qatar'         );
--insert into llx_c_pays (rowid,code,libelle) values (187, 'RE', 'Réunion'       );
insert into llx_c_pays (rowid,code,libelle) values (188, 'RO', 'Roumanie'      );
insert into llx_c_pays (rowid,code,libelle) values (189, 'RW', 'Rwanda'        );
insert into llx_c_pays (rowid,code,libelle) values (190, 'SH', 'Sainte-Hélène' );
insert into llx_c_pays (rowid,code,libelle) values (191, 'KN', 'Saint-Christophe-et-Niévès');
insert into llx_c_pays (rowid,code,libelle) values (192, 'LC', 'Sainte-Lucie'  );
insert into llx_c_pays (rowid,code,libelle) values (193, 'PM', 'Saint-Pierre-et-Miquelon');
insert into llx_c_pays (rowid,code,libelle) values (194, 'VC', 'Saint-Vincent-et-les-Grenadines');
insert into llx_c_pays (rowid,code,libelle) values (195, 'WS', 'Samoa'         );
insert into llx_c_pays (rowid,code,libelle) values (196, 'SM', 'Saint-Marin'   );
insert into llx_c_pays (rowid,code,libelle) values (197, 'ST', 'Sao Tomé-et-Principe');
insert into llx_c_pays (rowid,code,libelle) values (198, 'RS', 'Serbie'        );
insert into llx_c_pays (rowid,code,libelle) values (199, 'SC', 'Seychelles'    );
insert into llx_c_pays (rowid,code,libelle) values (200, 'SL', 'Sierra Leone'  );
insert into llx_c_pays (rowid,code,libelle) values (201, 'SK', 'Slovaquie'     );
insert into llx_c_pays (rowid,code,libelle) values (202, 'SI', 'Slovénie'      );
insert into llx_c_pays (rowid,code,libelle) values (203, 'SB', 'Iles Salomon'  );
insert into llx_c_pays (rowid,code,libelle) values (204, 'SO', 'Somalie'       );
insert into llx_c_pays (rowid,code,libelle) values (205, 'ZA', 'Afrique du Sud');
insert into llx_c_pays (rowid,code,libelle) values (206, 'GS', 'Iles Géorgie du Sud et Sandwich du Sud');
insert into llx_c_pays (rowid,code,libelle) values (207, 'LK', 'Sri Lanka'     );
insert into llx_c_pays (rowid,code,libelle) values (208, 'SD', 'Soudan'        );
insert into llx_c_pays (rowid,code,libelle) values (209, 'SR', 'Suriname'      );
insert into llx_c_pays (rowid,code,libelle) values (210, 'SJ', 'Iles Svalbard et Jan Mayen');
insert into llx_c_pays (rowid,code,libelle) values (211, 'SZ', 'Swaziland'     );
insert into llx_c_pays (rowid,code,libelle) values (212, 'SY', 'Syrie'         );
insert into llx_c_pays (rowid,code,libelle) values (213, 'TW', 'Taïwan'        );
insert into llx_c_pays (rowid,code,libelle) values (214, 'TJ', 'Tadjikistan'   );
insert into llx_c_pays (rowid,code,libelle) values (215, 'TZ', 'Tanzanie'      );
insert into llx_c_pays (rowid,code,libelle) values (216, 'TH', 'Thaïlande'     );
insert into llx_c_pays (rowid,code,libelle) values (217, 'TL', 'Timor Oriental');
insert into llx_c_pays (rowid,code,libelle) values (218, 'TK', 'Tokélaou'      );
insert into llx_c_pays (rowid,code,libelle) values (219, 'TO', 'Tonga'         );
insert into llx_c_pays (rowid,code,libelle) values (220, 'TT', 'Trinité-et-Tobago');
insert into llx_c_pays (rowid,code,libelle) values (221, 'TR', 'Turquie'       );
insert into llx_c_pays (rowid,code,libelle) values (222, 'TM', 'Turkménistan'  );
insert into llx_c_pays (rowid,code,libelle) values (223, 'TC', 'Iles Turks-et-Caicos');
insert into llx_c_pays (rowid,code,libelle) values (224, 'TV', 'Tuvalu'        );
insert into llx_c_pays (rowid,code,libelle) values (225, 'UG', 'Ouganda'       );
insert into llx_c_pays (rowid,code,libelle) values (226, 'UA', 'Ukraine'       );
insert into llx_c_pays (rowid,code,libelle) values (227, 'AE', 'Émirats arabes unis');
insert into llx_c_pays (rowid,code,libelle) values (228, 'UM', 'Iles mineures éloignées des États-Unis');
insert into llx_c_pays (rowid,code,libelle) values (229, 'UY', 'Uruguay'       );
insert into llx_c_pays (rowid,code,libelle) values (230, 'UZ', 'Ouzbékistan'   );
insert into llx_c_pays (rowid,code,libelle) values (231, 'VU', 'Vanuatu'       );
insert into llx_c_pays (rowid,code,libelle) values (232, 'VE', 'Vénézuela'     );
insert into llx_c_pays (rowid,code,libelle) values (233, 'VN', 'Viêt Nam'      );
insert into llx_c_pays (rowid,code,libelle) values (234, 'VG', 'Iles Vierges britanniques');
insert into llx_c_pays (rowid,code,libelle) values (235, 'VI', 'Iles Vierges américaines');
insert into llx_c_pays (rowid,code,libelle) values (236, 'WF', 'Wallis-et-Futuna');
insert into llx_c_pays (rowid,code,libelle) values (237, 'EH', 'Sahara occidental');
insert into llx_c_pays (rowid,code,libelle) values (238, 'YE', 'Yémen'         );
insert into llx_c_pays (rowid,code,libelle) values (239, 'ZM', 'Zambie'        );
insert into llx_c_pays (rowid,code,libelle) values (240, 'ZW', 'Zimbabwe'      );
insert into llx_c_pays (rowid,code,libelle) values (241, 'GG', 'Guernesey'     );
insert into llx_c_pays (rowid,code,libelle) values (242, 'IM', 'Ile de Man'    );
insert into llx_c_pays (rowid,code,libelle) values (243, 'JE', 'Jersey'        );
insert into llx_c_pays (rowid,code,libelle) values (244, 'ME', 'Monténégro'    );
insert into llx_c_pays (rowid,code,libelle) values (245, 'BL', 'Saint-Barthélemy');
insert into llx_c_pays (rowid,code,libelle) values (246, 'MF', 'Saint-Martin'  );
