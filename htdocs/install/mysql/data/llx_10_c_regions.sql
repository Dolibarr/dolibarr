-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
-- Copyright (C) 2010-2016 Juanjo Menent        <jmenent@2byte.es>
-- Copyright (C) 2012      Sebastian Neuwert    <sebastian.neuwert@modula71.de>
-- Copyright (C) 2012	   Ricardo Schluter     <info@ripasch.nl>
-- Copyright (C) 2015	   Ferran Marcet        <fmarcet@2byte.es>
-- Copyright (C) 2019~	   Lao Tian        <281388879@qq.com>
-- Copyright (C) 2020-2021 Udo Tamm             <dev@dolibit.de>
-- Copyright (C) 2022      Miro Sertić          <miro.sertic0606@gmail.com>
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


-- WARNING ------------------------------------------------------------------
--
-- Do not add comments at the end of the lines, this file is parsed during
-- the install and all '--' prefixed texts are are removed.
-- Do not concatenate the values in a single query, for the same reason.
--


-- NOTES ---------------------------------------------------------------------
-- fk_pays = id country = rowid of llx_00_c_country.sql


-- CONTENT -------------------------------------------------------------------
-- 
-- Algeria -> for Departmements
-- Andorra -> for Departmements
-- Angola -> for Departmements
-- Argentina
-- Australia -> for Departmements
-- Austria -> for Departmements
-- Barbados -> for Departmements
-- Belgium
-- Bolivia
-- Brazil -> for Departmements
-- Canada -> for Departmements
-- Chile
-- China
-- Colombie -> for Departmements
-- Croatia -> for Departmements
-- Denmark
-- France
-- Germany -> for Departmements
-- Greece
-- Honduras -> for Departmements
-- Hungary
-- India -> for Departmements
-- Indonesia -> for Departmements
-- Italy
-- Luxembourg
-- Mauritius
-- Mexique -> for Departmements
-- Morocco
-- Netherlands -> for Departmements
-- Panama -> for Departmements
-- Peru
-- Portugal
-- Romania -> for Departmements
-- San Salvador
-- Slovenia
-- Spain
-- Switzerland/Suisse -> for Departmements/Cantons
-- Taiwan -> for Departmements
-- Tunesia
-- United Arab Emirates -> for Departmements
-- United Kingdom
-- USA -> for Departmements
-- Venezuela



-- TEMPLATE ------------------------------------------------------------------------------------------
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 0,  0, '0', 0, '-');


-- Algeria Regions (id country=13)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 13, 1301, '', 0, 'Algerie');


-- Andorra Regions (id country=34)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 34, 34000, 'AD', NULL, 'Andorra');


-- Angola Regions (id country=35)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 35, 35001, 'AO', NULL, 'Angola');


-- Argentina Regions (id country=23)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 23, 2301, '', 0, 'Norte');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 23, 2302, '', 0, 'Litoral');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 23, 2303, '', 0, 'Cuyana');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 23, 2304, '', 0, 'Central');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 23, 2305, '', 0, 'Patagonia');


-- Australia Regions (id country=28)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 28, 2801, '', 0, 'Australia');


-- Austria Regions (id country=41)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 41, 4101, '', 0, 'Österreich');


-- Barbados Regions (id country=46)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 46, 4601, '', 0, 'Barbados');


-- Belgium Regions (id country=2)
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 2, 201, '', 1, 'Flandre');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 2, 202, '', 2, 'Wallonie');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 2, 203, '', 3, 'Bruxelles-Capitale');


-- Bolivia Regions (id country=52)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 52, 5201, '', 0, 'Chuquisaca');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 52, 5202, '', 0, 'La Paz');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 52, 5203, '', 0, 'Cochabamba');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 52, 5204, '', 0, 'Oruro');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 52, 5205, '', 0, 'Potosí');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 52, 5206, '', 0, 'Tarija');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 52, 5207, '', 0, 'Santa Cruz');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 52, 5208, '', 0, 'El Beni');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 52, 5209, '', 0, 'Pando');


-- Brazil Regions (id country=56)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 56, 5601, '', 0, 'Brasil');


-- Burundi Regions (id country=61) -- https://fr.wikipedia.org/wiki/Provinces_du_Burundi
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 61, 6101, '', 0, 'Bubanza');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 61, 6102, '', 0, 'Bujumbura Mairie');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 61, 6103, '', 0, 'Bujumbura Rural');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 61, 6104, '', 0, 'Bururi');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 61, 6105, '', 0, 'Cankuzo');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 61, 6106, '', 0, 'Cibitoke');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 61, 6107, '', 0, 'Gitega');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 61, 6108, '', 0, 'Karuzi');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 61, 6109, '', 0, 'Kayanza');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 61, 6110, '', 0, 'Kirundo');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 61, 6111, '', 0, 'Makamba');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 61, 6112, '', 0, 'Muramvya');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 61, 6113, '', 0, 'Muyinga');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 61, 6114, '', 0, 'Mwaro');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 61, 6115, '', 0, 'Ngozi');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 61, 6116, '', 0, 'Rumonge');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 61, 6117, '', 0, 'Rutana');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 61, 6118, '', 0, 'Ruyigi');


-- Canada Region (id country=14)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 14, 1401, '', 0, 'Canada');


-- Chile Regions (id country=67)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 67, 6701, NULL, NULL, 'Tarapacá');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 67, 6702, NULL, NULL, 'Antofagasta');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 67, 6703, NULL, NULL, 'Atacama');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 67, 6704, NULL, NULL, 'Coquimbo');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 67, 6705, NULL, NULL, 'Valparaíso');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 67, 6706, NULL, NULL, 'General Bernardo O Higgins');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 67, 6707, NULL, NULL, 'Maule');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 67, 6708, NULL, NULL, 'Biobío');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 67, 6709, NULL, NULL, 'Raucanía');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 67, 6710, NULL, NULL, 'Los Lagos');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 67, 6711, NULL, NULL, 'Aysén General Carlos Ibáñez del Campo');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 67, 6712, NULL, NULL, 'Magallanes y Antártica Chilena');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 67, 6713, NULL, NULL, 'Metropolitana de Santiago');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 67, 6714, NULL, NULL, 'Los Ríos');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 67, 6715, NULL, NULL, 'Arica y Parinacota');


-- China Regions (rowid country=9)
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 901, '京',0, '北京市');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 902, '津',0, '天津市');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 903, '沪',0, '上海市');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 904, '渝',0, '重庆市');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 905, '冀',0, '河北省');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 906, '晋',0, '山西省');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 907, '辽',0, '辽宁省');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 908, '吉',0, '吉林省');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 909, '黑',0, '黑龙江省');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 910, '苏',0, '江苏省');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 911, '浙',0, '浙江省');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 912, '皖',0, '安徽省');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 913, '闽',0, '福建省');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 914, '赣',0, '江西省');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 915, '鲁',0, '山东省');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 916, '豫',0, '河南省');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 917, '鄂',0, '湖北省');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 918, '湘',0, '湖南省');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 919, '粤',0, '广东省');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 920, '琼',0, '海南省');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 921, '川',0, '四川省');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 922, '贵',0, '贵州省');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 923, '云',0, '云南省');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 924, '陕',0, '陕西省');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 925, '甘',0, '甘肃省');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 926, '青',0, '青海省');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 927, '台',0, '台湾省');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 928, '蒙',0, '内蒙古自治区');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 929, '桂',0, '广西壮族自治区');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 930, '藏',0, '西藏自治区');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 931, '宁',0, '宁夏回族自治区');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 932, '新',0, '新疆维吾尔自治区');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 933, '港',0, '香港特别行政区');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 9, 934, '澳',0, '澳门特别行政区');


-- Colombie Regions (id country=70)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 70, 7001, '', 0, 'Colombie');


-- Croatia Regions (id country=76)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 76, 7601, '', 0, 'Središnja');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 76, 7602, '', 0, 'Dalmacija');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 76, 7603, '', 0, 'Slavonija');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 76, 7604, '', 0, 'Istra');


-- Denmark Regions (id country=80)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 80, 8001, '', 0, 'Nordjylland');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 80, 8002, '', 0, 'Midtjylland');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 80, 8003, '', 0, 'Syddanmark');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 80, 8004, '', 0, 'Hovedstaden');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 80, 8005, '', 0, 'Sjælland');


-- France Regions (id country=1)
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 1,  1, '97105', 3, 'Guadeloupe');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 1,  2, '97209', 3, 'Martinique');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 1,  3, '97302', 3, 'Guyane');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 1,  4, '97411', 3, 'Réunion');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 1,  6, '97601', 3, 'Mayotte');

insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 1, 11, '75056', 1, 'Île-de-France');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 1, 24, '45234', 2, 'Centre-Val de Loire');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 1, 27, '21231', 0, 'Bourgogne-Franche-Comté');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 1, 28, '76540', 0, 'Normandie');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 1, 32, '59350', 4, 'Hauts-de-France');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 1, 44, '67482', 2, 'Grand Est');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 1, 52, '44109', 4, 'Pays de la Loire');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 1, 53, '35238', 0, 'Bretagne');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 1, 75, '33063', 0, 'Nouvelle-Aquitaine');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 1, 76, '31355', 1, 'Occitanie');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 1, 84, '69123', 1, 'Auvergne-Rhône-Alpes');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 1, 93, '13055', 0, 'Provence-Alpes-Côte d''Azur');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 1, 94, '2A004', 0, 'Corse');


-- Germany Regions (id country=5)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 5, 501, '', 0, 'Deutschland');


-- Greece Regions (id_country=102)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 102, 10201, NULL, NULL, 'Αττική');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 102, 10202, NULL, NULL, 'Στερεά Ελλάδα');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 102, 10203, NULL, NULL, 'Κεντρική Μακεδονία');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 102, 10204, NULL, NULL, 'Κρήτη');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 102, 10205, NULL, NULL, 'Ανατολική Μακεδονία και Θράκη');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 102, 10206, NULL, NULL, 'Ήπειρος');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 102, 10207, NULL, NULL, 'Ιόνια νησιά');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 102, 10208, NULL, NULL, 'Βόρειο Αιγαίο');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 102, 10209, NULL, NULL, 'Πελοπόννησος');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 102, 10210, NULL, NULL, 'Νότιο Αιγαίο');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 102, 10211, NULL, NULL, 'Δυτική Ελλάδα');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 102, 10212, NULL, NULL, 'Θεσσαλία');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 102, 10213, NULL, NULL, 'Δυτική Μακεδονία');


-- Honduras Regions (id country=114)
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 114, 11401, '', 0, 'Honduras');


-- Hungary Regions (rowid country=18)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 18, 180100, 'HU1',  NULL, 'Közép-Magyarország');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 18, 182100, 'HU21', NULL, 'Közép-Dunántúl');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 18, 182200, 'HU22', NULL, 'Nyugat-Dunántúl');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 18, 182300, 'HU23', NULL, 'Dél-Dunántúl');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 18, 183100, 'HU31', NULL, 'Észak-Magyarország');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 18, 183200, 'HU32', NULL, 'Észak-Alföld');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 18, 183300, 'HU33', NULL, 'Dél-Alföld');


-- India Regions (id country=117)
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 117, 11701, '', 0, 'India');


-- Indonesia Regions (id country=118)
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 118, 11801, '', 0, 'Indonesia');


-- Italy Regions (id country=3)
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 3, 301, NULL, 1, 'Abruzzo');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 3, 302, NULL, 1, 'Basilicata');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 3, 303, NULL, 1, 'Calabria');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 3, 304, NULL, 1, 'Campania');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 3, 305, NULL, 1, 'Emilia-Romagna');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 3, 306, NULL, 1, 'Friuli-Venezia Giulia');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 3, 307, NULL, 1, 'Lazio');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 3, 308, NULL, 1, 'Liguria');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 3, 309, NULL, 1, 'Lombardia');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 3, 310, NULL, 1, 'Marche');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 3, 311, NULL, 1, 'Molise');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 3, 312, NULL, 1, 'Piemonte');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 3, 313, NULL, 1, 'Puglia');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 3, 314, NULL, 1, 'Sardegna');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 3, 315, NULL, 1, 'Sicilia');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 3, 316, NULL, 1, 'Toscana');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 3, 317, NULL, 1, 'Trentino-Alto Adige');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 3, 318, NULL, 1, 'Umbria');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 3, 319, NULL, 1, 'Valle d Aosta');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 3, 320, NULL, 1, 'Veneto');


-- Luxembourg Regions (districts) (id country=140)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 140, 14001, '', 0, 'Diekirch');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 140, 14002, '', 0, 'Grevenmacher');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 140, 14003, '', 0, 'Luxembourg');


-- Mauritius Regions (id country=152)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 152, 15201, '', 0, 'Rivière Noire');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 152, 15202, '', 0, 'Flacq');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 152, 15203, '', 0, 'Grand Port');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 152, 15204, '', 0, 'Moka');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 152, 15205, '', 0, 'Pamplemousses');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 152, 15206, '', 0, 'Plaines Wilhems');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 152, 15207, '', 0, 'Port-Louis');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 152, 15208, '', 0, 'Rivière du Rempart');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 152, 15209, '', 0, 'Savanne');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 152, 15210, '', 0, 'Rodrigues');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 152, 15211, '', 0, 'Les îles Agaléga');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 152, 15212, '', 0, 'Les écueils des Cargados Carajos');


-- Mexique Regions (id country=154)
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 154, 15401, '', 0, 'Mexique');


-- Morocco / Maroc - Regions since 2015 (id country=12)
-- INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 12, 1201, '', 0, 'Tanger-Tétouan-Al Hoceima');
-- INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 12, 1202, '', 0, 'Oriental');
-- INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 12, 1203, '', 0, 'Fès-Meknès');                                                                
-- INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 12, 1204, '', 0, 'Rabat-Salé-Kénitra');
-- INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 12, 1205, '', 0, 'Béni Mellal-Khénifra');
-- INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 12, 1206, '', 0, 'Casablanca-Settat');
-- INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 12, 1207, '', 0, 'Marrakech-Safi');
-- INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 12, 1208, '', 0, 'Drâa-Tafilalet');
-- INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 12, 1209, '', 0, 'Souss-Massa');
-- INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 12, 1210, '', 0, 'Guelmim-Oued Noun');
-- INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 12, 1211, '', 0, 'Laâyoune-Sakia El Hamra');
-- INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 12, 1212, '', 0, 'Dakhla-Oued Ed Dahab');


-- Morocco / Maroc - History 1997-2015 - Regions 13 +3 of Moroccan Sahara (id country=12)
INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 12, 1201, '', 0, 'Tanger-Tétouan');
INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 12, 1202, '', 0, 'Gharb-Chrarda-Beni Hssen');
INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 12, 1203, '', 0, 'Taza-Al Hoceima-Taounate');
INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 12, 1204, '', 0, 'L''Oriental');
INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 12, 1205, '', 0, 'Fès-Boulemane');
INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 12, 1206, '', 0, 'Meknès-Tafialet');
INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 12, 1207, '', 0, 'Rabat-Salé-Zemour-Zaër');
INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 12, 1208, '', 0, 'Grand Cassablanca');
INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 12, 1209, '', 0, 'Chaouia-Ouardigha');
INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 12, 1210, '', 0, 'Doukahla-Adba');
INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 12, 1211, '', 0, 'Marrakech-Tensift-Al Haouz');
INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 12, 1212, '', 0, 'Tadla-Azilal');
INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 12, 1213, '', 0, 'Sous-Massa-Drâa');
INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 12, 1214, '', 0, 'Guelmim-Es Smara');
INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 12, 1215, '', 0, 'Laâyoune-Boujdour-Sakia el Hamra');
INSERT INTO  llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 12, 1216, '', 0, 'Oued Ed-Dahab Lagouira');



-- Netherlands Regions (id country=17)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 17, 1701, '', 0,'Provincies van Nederland ');


-- Panama Regions (id country=178)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 178, 17801, '', 0, 'Panama');


-- Peru Regions (id country=181)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 181, 18101, '', 0, 'Amazonas');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 181, 18102, '', 0, 'Ancash');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 181, 18103, '', 0, 'Apurimac');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 181, 18104, '', 0, 'Arequipa');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 181, 18105, '', 0, 'Ayacucho');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 181, 18106, '', 0, 'Cajamarca');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 181, 18107, '', 0, 'Callao');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 181, 18108, '', 0, 'Cuzco');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 181, 18109, '', 0, 'Huancavelica');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 181, 18110, '', 0, 'Huanuco');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 181, 18111, '', 0, 'Ica');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 181, 18112, '', 0, 'Junin');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 181, 18113, '', 0, 'La Libertad');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 181, 18114, '', 0, 'Lambayeque');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 181, 18115, '', 0, 'Lima Metropolitana');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 181, 18116, '', 0, 'Lima');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 181, 18117, '', 0, 'Loreto');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 181, 18118, '', 0, 'Madre de Dios');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 181, 18119, '', 0, 'Moquegua');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 181, 18120, '', 0, 'Pasco');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 181, 18121, '', 0, 'Piura');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 181, 18122, '', 0, 'Puno');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 181, 18123, '', 0, 'San Martín');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 181, 18124, '', 0, 'Tacna');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 181, 18125, '', 0, 'Tumbes');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 181, 18126, '', 0, 'Ucayali');


-- Portugal Regions (rowid country=25)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 25, 15001, 'PT', NULL, 'Portugal');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 25, 15002, 'PT9', NULL, 'Azores-Madeira');


-- Romania Regions (id country=188) 
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 188, 18801, '', 0, 'Romania');


-- San Salvador Regions (id country=86)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 86, 8601, NULL, NULL, 'Central');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 86, 8602, NULL, NULL, 'Oriental');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 86, 8603, NULL, NULL, 'Occidental');


-- Slovenia Regions (rowid country=202)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 202, '20203', 'SI03', NULL, 'East Slovenia');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 202, '20204', 'SI04', NULL, 'West Slovenia');


-- Spain  Regions (id country=4)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 4, 401, '', 0, 'Andalucia');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 4, 402, '', 0, 'Aragón');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 4, 403, '', 0, 'Castilla y León');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 4, 404, '', 0, 'Castilla la Mancha');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 4, 405, '', 0, 'Canarias');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 4, 406, '', 0, 'Cataluña');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 4, 407, '', 0, 'Comunidad de Ceuta');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 4, 408, '', 0, 'Comunidad Foral de Navarra');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 4, 409, '', 0, 'Comunidad de Melilla');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 4, 410, '', 0, 'Cantabria');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 4, 411, '', 0, 'Comunidad Valenciana');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 4, 412, '', 0, 'Extemadura');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 4, 413, '', 0, 'Galicia');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 4, 414, '', 0, 'Islas Baleares');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 4, 415, '', 0, 'La Rioja');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 4, 416, '', 0, 'Comunidad de Madrid');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 4, 417, '', 0, 'Región de Murcia');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 4, 418, '', 0, 'Principado de Asturias');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 4, 419, '', 0, 'Pais Vasco');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 4, 420, '', 0, 'Otros');


-- Switzerland Regions (id country=6) 
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 6, 601, '', 1, 'Cantons'); 


-- Taiwan Region (rowid country=213)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) VALUES ( 213, 21301, 'TW', NULL, 'Taiwan');


-- Tunisia Regions (id country=10)
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 10, 1001, '', 0, 'Ariana');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 10, 1002, '', 0, 'Béja');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 10, 1003, '', 0, 'Ben Arous');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 10, 1004, '', 0, 'Bizerte');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 10, 1005, '', 0, 'Gabès');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 10, 1006, '', 0, 'Gafsa');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 10, 1007, '', 0, 'Jendouba');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 10, 1008, '', 0, 'Kairouan');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 10, 1009, '', 0, 'Kasserine');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 10, 1010, '', 0, 'Kébili');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 10, 1011, '', 0, 'La Manouba');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 10, 1012, '', 0, 'Le Kef');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 10, 1013, '', 0, 'Mahdia');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 10, 1014, '', 0, 'Médenine');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 10, 1015, '', 0, 'Monastir');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 10, 1016, '', 0, 'Nabeul');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 10, 1017, '', 0, 'Sfax');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 10, 1018, '', 0, 'Sidi Bouzid');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 10, 1019, '', 0, 'Siliana');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 10, 1020, '', 0, 'Sousse');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 10, 1021, '', 0, 'Tataouine');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 10, 1022, '', 0, 'Tozeur');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 10, 1023, '', 0, 'Tunis');
insert into llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 10, 1024, '', 0, 'Zaghouan');


-- United Arab Emirates (UAE) Region (rowid country=227)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 227, 22701, '', 0, 'United Arab Emirates');


-- UK United Kingdom Regions (id_country=7)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 7, 701, '', 0, 'England');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 7, 702, '', 0, 'Wales');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 7, 703, '', 0, 'Scotland');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 7, 704, '', 0, 'Northern Ireland');


-- USA Region (id country=11)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 11, 1101, '', 0, 'United-States');


-- Venezuela Regions (id country=232)
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 232, 23201, '', 0, 'Los Andes');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 232, 23202, '', 0, 'Capital');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 232, 23203, '', 0, 'Central');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 232, 23204, '', 0, 'Cento Occidental');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 232, 23205, '', 0, 'Guayana');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 232, 23206, '', 0, 'Insular');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 232, 23207, '', 0, 'Los Llanos');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 232, 23208, '', 0, 'Nor-Oriental');
INSERT INTO llx_c_regions (fk_pays, code_region, cheflieu, tncc, nom) values ( 232, 23209, '', 0, 'Zuliana');

