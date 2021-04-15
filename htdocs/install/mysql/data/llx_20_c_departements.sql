-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
-- Copyright (C) 2010-2016 Juanjo Menent        <jmenent@2byte.es>
-- Copyright (C) 2012 	   Sebastian Neuwert    <sebastian.neuwert@modula71.de>
-- Copyright (C) 2012	     Ricardo Schluter     <info@ripasch.nl>
-- Copyright (C) 2015	     Ferran Marcet	      <fmarcet@2byte.es>
-- Copyright (C) 2020-2021 Udo Tamm             <dev@dolibit.de>
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


-- WARNING ---------------------------------------------------------------------
-- Do not put comments at the end of the lines, this file is parsed during
-- the install and all '-' prefixed texts are removed.
-- Do not concatenate the values in a single query, for the same reason.


-- NOTES/CONTENT ---------------------------------------------------------------
-- Departements/Cantons/Provinces/States
--
-- Algeria
-- Andorra
-- Angola
-- Argentina
-- Australia
-- Austria
-- Barbados
-- Belgium
-- Brazil
-- Canada
-- Chile
-- Colombia
-- France
-- Germany
-- Honduras
-- Hungary
-- (Italy)
-- Luxembourg
-- Netherlands
-- (Moroco)
-- Romania 



-- TEMPLATE -------------------------------------------------------------------------------------------------------------
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values ( 0, '0',  '0',0,'-','-');


-- Algeria Provinces  (id country=13)
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL01', '', 0, '', 'Wilaya d''Adrar');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL02', '', 0, '', 'Wilaya de Chlef');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL03', '', 0, '', 'Wilaya de Laghouat');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL04', '', 0, '', 'Wilaya d''Oum El Bouaghi');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL05', '', 0, '', 'Wilaya de Batna');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL06', '', 0, '', 'Wilaya de Béjaïa');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL07', '', 0, '', 'Wilaya de Biskra');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL08', '', 0, '', 'Wilaya de Béchar');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL09', '', 0, '', 'Wilaya de Blida');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL10', '', 0, '', 'Wilaya de Bouira');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL11', '', 0, '', 'Wilaya de Tamanrasset');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL12', '', 0, '', 'Wilaya de Tébessa');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL13', '', 0, '', 'Wilaya de Tlemcen');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL14', '', 0, '', 'Wilaya de Tiaret');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL15', '', 0, '', 'Wilaya de Tizi Ouzou');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL16', '', 0, '', 'Wilaya d''Alger');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL17', '', 0, '', 'Wilaya de Djelfa');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL18', '', 0, '', 'Wilaya de Jijel');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL19', '', 0, '', 'Wilaya de Sétif');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL20', '', 0, '', 'Wilaya de Saïda');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL21', '', 0, '', 'Wilaya de Skikda');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL22', '', 0, '', 'Wilaya de Sidi Bel Abbès');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL23', '', 0, '', 'Wilaya d''Annaba');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL24', '', 0, '', 'Wilaya de Guelma');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL25', '', 0, '', 'Wilaya de Constantine');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL26', '', 0, '', 'Wilaya de Médéa');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL27', '', 0, '', 'Wilaya de Mostaganem');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL28', '', 0, '', 'Wilaya de M''Sila');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL29', '', 0, '', 'Wilaya de Mascara');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL30', '', 0, '', 'Wilaya d''Ouargla');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL31', '', 0, '', 'Wilaya d''Oran');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL32', '', 0, '', 'Wilaya d''El Bayadh');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL33', '', 0, '', 'Wilaya d''Illizi');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL34', '', 0, '', 'Wilaya de Bordj Bou Arreridj');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL35', '', 0, '', 'Wilaya de Boumerdès');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL36', '', 0, '', 'Wilaya d''El Tarf');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL37', '', 0, '', 'Wilaya de Tindouf');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL38', '', 0, '', 'Wilaya de Tissemsilt');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL39', '', 0, '', 'Wilaya d''El Oued');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL40', '', 0, '', 'Wilaya de Khenchela');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL41', '', 0, '', 'Wilaya de Souk Ahras');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL42', '', 0, '', 'Wilaya de Tipaza');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL43', '', 0, '', 'Wilaya de Mila');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL44', '', 0, '', 'Wilaya d''Aïn Defla');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL45', '', 0, '', 'Wilaya de Naâma');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL46', '', 0, '', 'Wilaya d''Aïn Témouchent');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL47', '', 0, '', 'Wilaya de Ghardaia');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, 'AL48', '', 0, '', 'Wilaya de Relizane');


-- Andorra Parròquies (id country=34)
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (34000, 'AD-002', 'AD100', NULL, NULL, 'Canillo');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (34000, 'AD-003', 'AD200', NULL, NULL, 'Encamp');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (34000, 'AD-004', 'AD400', NULL, NULL, 'La Massana');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (34000, 'AD-005', 'AD300', NULL, NULL, 'Ordino');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (34000, 'AD-006', 'AD600', NULL, NULL, 'Sant Julià de Lòria');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (34000, 'AD-007', 'AD500', NULL, NULL, 'Andorra la Vella');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (34000, 'AD-008', 'AD700', NULL, NULL, 'Escaldes-Engordany');


-- Angola Provinces (postal districts) (id country=35)
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (35001, 'AO-ABO', NULL, NULL, 'BENGO', 'Bengo');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (35001, 'AO-BGU', NULL, NULL, 'BENGUELA', 'Benguela');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (35001, 'AO-BIE', NULL, NULL, 'BIÉ', 'Bié');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (35001, 'AO-CAB', NULL, NULL, 'CABINDA', 'Cabinda');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (35001, 'AO-CCU', NULL, NULL, 'KUANDO KUBANGO', 'Kuando Kubango');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (35001, 'AO-CNO', NULL, NULL, 'KWANZA NORTE', 'Kwanza Norte');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (35001, 'AO-CUS', NULL, NULL, 'KWANZA SUL', 'Kwanza Sul');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (35001, 'AO-CNN', NULL, NULL, 'CUNENE', 'Cunene');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (35001, 'AO-HUA', NULL, NULL, 'HUAMBO', 'Huambo');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (35001, 'AO-HUI', NULL, NULL, 'HUÍLA', 'Huila');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (35001, 'AO-LUA', NULL, NULL, 'LUANDA', 'Luanda');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (35001, 'AO-LNO', NULL, NULL, 'LUNDA-NORTE', 'Lunda-Norte');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (35001, 'AO-LSU',NULL, NULL,  'LUNDA-SUL', 'Lunda-Sul');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (35001, 'AO-MAL', NULL, NULL, 'MALANGE', 'Malange');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (35001, 'AO-MOX', NULL, NULL, 'MOXICO', 'Moxico');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (35001, 'AO-NAM', NULL, NULL, 'NAMÍBE', 'Namíbe');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (35001, 'AO-UIG', NULL, NULL, 'UÍGE', 'Uíge');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (35001, 'AO-ZAI', NULL, NULL, 'ZAÍRE', 'Zaíre');


-- Argentina Provinces / provincias (id country=23)
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2301, '2301', '', 0, 'CATAMARCA', 'Catamarca');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2301, '2302', '', 0, 'JUJUY', 'Jujuy');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2301, '2303', '', 0, 'TUCAMAN', 'Tucamán');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2301, '2304', '', 0, 'SANTIAGO DEL ESTERO', 'Santiago del Estero');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2301, '2305', '', 0, 'SALTA', 'Salta');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2302, '2306', '', 0, 'CHACO', 'Chaco');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2302, '2307', '', 0, 'CORRIENTES', 'Corrientes');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2302, '2308', '', 0, 'ENTRE RIOS', 'Entre Ríos');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2302, '2309', '', 0, 'FORMOSA', 'Formosa');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2302, '2310', '', 0, 'SANTA FE', 'Santa Fe');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2303, '2311', '', 0, 'LA RIOJA', 'La Rioja');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2303, '2312', '', 0, 'MENDOZA', 'Mendoza');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2303, '2313', '', 0, 'SAN JUAN', 'San Juan');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2303, '2314', '', 0, 'SAN LUIS', 'San Luis');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2304, '2315', '', 0, 'CORDOBA', 'Córdoba');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2304, '2316', '', 0, 'BUENOS AIRES', 'Buenos Aires');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2304, '2317', '', 0, 'CABA', 'Caba');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2305, '2318', '', 0, 'LA PAMPA', 'La Pampa');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2305, '2319', '', 0, 'NEUQUEN', 'Neuquén');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2305, '2320', '', 0, 'RIO NEGRO', 'Río Negro');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2305, '2321', '', 0, 'CHUBUT', 'Chubut');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2305, '2322', '', 0, 'SANTA CRUZ', 'Santa Cruz');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2305, '2323', '', 0, 'TIERRA DEL FUEGO', 'Tierra del Fuego');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2305, '2324', '', 0, 'ISLAS MALVINAS', 'Islas Malvinas');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2305, '2325', '', 0, 'ANTARTIDA', 'Antártida');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2305, '2326', '', 0, 'MISIONES', 'Misiones');


-- Australia States & Territories (id country=28)
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (2801, 'NSW','',1,'','New South Wales');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (2801, 'VIC','',1,'','Victoria');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (2801, 'QLD','',1,'','Queensland');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (2801, 'SA' ,'',1,'','South Australia');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (2801, 'ACT','',1,'','Australia Capital Territory');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (2801, 'TAS','',1,'','Tasmania');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (2801, 'WA' ,'',1,'','Western Australia');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (2801, 'NT' ,'',1,'','Northern Territory');


-- Austria States / Österreich Bundesländer (id country=41)
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (4101, 'B','BURGENLAND','Burgenland',1);
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (4101, 'K','KAERNTEN','Kärnten',1);
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (4101, 'N','NIEDEROESTERREICH','Niederösterreich',1);
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (4101, 'O','OBEROESTERREICH','Oberösterreich',1);
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (4101, 'S','SALZBURG','Salzburg',1);
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (4101, 'ST','STEIERMARK','Steiermark',1);
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (4101, 'T','TIROL','Tirol',1);
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (4101, 'V','VORARLBERG','Vorarlberg',1);
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom, active) VALUES (4101, 'W','WIEN','Wien',1);


-- Barbados Parish (id country=46)
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (4601, 'CC', 'Oistins', 0, 'CC', 'Christ Church');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (4601, 'SA', 'Greenland', 0, 'SA', 'Saint Andrew');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (4601, 'SG', 'Bulkeley', 0, 'SG', 'Saint George');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (4601, 'JA', 'Holetown', 0, 'JA', 'Saint James');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (4601, 'SJ', 'Four Roads', 0, 'SJ', 'Saint John');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (4601, 'SB', 'Bathsheba', 0, 'SB', 'Saint Joseph');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (4601, 'SL', 'Crab Hill', 0, 'SL', 'Saint Lucy');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (4601, 'SM', 'Bridgetown', 0, 'SM', 'Saint Michael');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (4601, 'SP', 'Speightstown', 0, 'SP', 'Saint Peter');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (4601, 'SC', 'Crane', 0, 'SC', 'Saint Philip');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (4601, 'ST', 'Hillaby', 0, 'ST', 'Saint Thomas');


-- Belgium Provinces (id country=2)
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (201, '01','',1,'ANVERS','Anvers');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (203, '02','',3,'BRUXELLES-CAPITALE','Bruxelles-Capitale');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (202, '03','',2,'BRABANT-WALLON','Brabant-Wallon');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (201, '04','',1,'BRABANT-FLAMAND','Brabant-Flamand');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (201, '05','',1,'FLANDRE-OCCIDENTALE','Flandre-Occidentale');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (201, '06','',1,'FLANDRE-ORIENTALE','Flandre-Orientale');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (202, '07','',2,'HAINAUT','Hainaut');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (201, '08','',2,'LIEGE','Liège');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (202, '09','',1,'LIMBOURG','Limbourg');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (202, '10','',2,'LUXEMBOURG','Luxembourg');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (201, '11','',2,'NAMUR','Namur');


-- Brazil Provinces (id country=56)
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (5601, 'AC', 'ACRE', 0, 'AC', 'Acre');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (5601, 'AL', 'ALAGOAS', 0, 'AL', 'Alagoas');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (5601, 'AP', 'AMAPA', 0, 'AP', 'Amapá');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (5601, 'AM', 'AMAZONAS', 0, 'AM', 'Amazonas');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (5601, 'BA', 'BAHIA', 0, 'BA', 'Bahia');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (5601, 'CE', 'CEARA', 0, 'CE', 'Ceará');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (5601, 'ES', 'ESPIRITO SANTO', 0, 'ES', 'Espirito Santo');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (5601, 'GO', 'GOIAS', 0, 'GO', 'Goiás');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (5601, 'MA', 'MARANHAO', 0, 'MA', 'Maranhão');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (5601, 'MT', 'MATO GROSSO', 0, 'MT', 'Mato Grosso');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (5601, 'MS', 'MATO GROSSO DO SUL', 0, 'MS', 'Mato Grosso do Sul');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (5601, 'MG', 'MINAS GERAIS', 0, 'MG', 'Minas Gerais');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (5601, 'PA', 'PARA', 0, 'PA', 'Pará');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (5601, 'PB', 'PARAIBA', 0, 'PB', 'Paraiba');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (5601, 'PR', 'PARANA', 0, 'PR', 'Paraná');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (5601, 'PE', 'PERNAMBUCO', 0, 'PE', 'Pernambuco');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (5601, 'PI', 'PIAUI', 0, 'PI', 'Piauí');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (5601, 'RJ', 'RIO DE JANEIRO', 0, 'RJ', 'Rio de Janeiro');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (5601, 'RN', 'RIO GRANDE DO NORTE', 0, 'RN', 'Rio Grande do Norte');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (5601, 'RS', 'RIO GRANDE DO SUL', 0, 'RS', 'Rio Grande do Sul');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (5601, 'RO', 'RONDONIA', 0, 'RO', 'Rondônia');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (5601, 'RR', 'RORAIMA', 0, 'RR', 'Roraima');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (5601, 'SC', 'SANTA CATARINA', 0, 'SC', 'Santa Catarina');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (5601, 'SE', 'SERGIPE', 0, 'SE', 'Sergipe');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (5601, 'SP', 'SAO PAULO', 0, 'SP', 'Sao Paulo');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (5601, 'TO', 'TOCANTINS', 0, 'TO', 'Tocantins');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (5601, 'DF', 'DISTRITO FEDERAL', 0, 'DF', 'Distrito Federal');


-- Canada Provinces & Territories (id country=14)
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1401, 'ON','',1,'','Ontario');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1401, 'QC','',1,'','Quebec');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1401, 'NS','',1,'','Nova Scotia');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1401, 'NB','',1,'','New Brunswick');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1401, 'MB','',1,'','Manitoba');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1401, 'BC','',1,'','British Columbia');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1401, 'PE','',1,'','Prince Edward Island');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1401, 'SK','',1,'','Saskatchewan');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1401, 'AB','',1,'','Alberta');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1401, 'NL','',1,'','Newfoundland and Labrador');


-- Chile Provinces (id country=67)
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6701, '011', '', 0, '011', 'Iquique');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6701, '014', '', 0, '014', 'Tamarugal');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6702, '021', '', 0, '021', 'Antofagasa');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6702, '022', '', 0, '022', 'El Loa');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6702, '023', '', 0, '023', 'Tocopilla');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6703, '031', '', 0, '031', 'Copiapó');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6703, '032', '', 0, '032', 'Chañaral');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6703, '033', '', 0, '033', 'Huasco');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6704, '041', '', 0, '041', 'Elqui');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6704, '042', '', 0, '042', 'Choapa');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6704, '043', '', 0, '043', 'Limarí');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6705, '051', '', 0, '051', 'Valparaíso');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6705, '052', '', 0, '052', 'Isla de Pascua');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6705, '053', '', 0, '053', 'Los Andes');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6705, '054', '', 0, '054', 'Petorca');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6705, '055', '', 0, '055', 'Quillota');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6705, '056', '', 0, '056', 'San Antonio');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6705, '057', '', 0, '057', 'San Felipe de Aconcagua');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6705, '058', '', 0, '058', 'Marga Marga');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6706, '061', '', 0, '061', 'Cachapoal');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6706, '062', '', 0, '062', 'Cardenal Caro');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6706, '063', '', 0, '063', 'Colchagua');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6707, '071', '', 0, '071', 'Talca');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6707, '072', '', 0, '072', 'Cauquenes');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6707, '073', '', 0, '073', 'Curicó');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6707, '074', '', 0, '074', 'Linares');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6708, '081', '', 0, '081', 'Concepción');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6708, '082', '', 0, '082', 'Arauco');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6708, '083', '', 0, '083', 'Biobío');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6708, '084', '', 0, '084', 'Ñuble');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6709, '091', '', 0, '091', 'Cautín');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6709, '092', '', 0, '092', 'Malleco');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6710, '101', '', 0, '101', 'Llanquihue');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6710, '102', '', 0, '102', 'Chiloé');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6710, '103', '', 0, '103', 'Osorno');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6710, '104', '', 0, '104', 'Palena');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6711, '111', '', 0, '111', 'Coihaique');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6711, '112', '', 0, '112', 'Aisén');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6711, '113', '', 0, '113', 'Capitán Prat');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6711, '114', '', 0, '114', 'General Carrera');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6712, '121', '', 0, '121', 'Magallanes');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6712, '122', '', 0, '122', 'Antártica Chilena');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6712, '123', '', 0, '123', 'Tierra del Fuego');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6712, '124', '', 0, '124', 'Última Esperanza');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6713, '131', '', 0, '131', 'Santiago');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6713, '132', '', 0, '132', 'Cordillera');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6713, '133', '', 0, '133', 'Chacabuco');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6713, '134', '', 0, '134', 'Maipo');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6713, '135', '', 0, '135', 'Melipilla');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6713, '136', '', 0, '136', 'Talagante');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6714, '141', '', 0, '141', 'Valdivia');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6714, '142', '', 0, '142', 'Ranco');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6715, '151', '', 0, '151', 'Arica');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6715, '152', '', 0, '152', 'Parinacota');


-- Colombia Departamentos (id country=70)
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'ANT', '', 0, 'ANT', 'Antioquia');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'BOL', '', 0, 'BOL', 'Bolívar');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'BOY', '', 0, 'BOY', 'Boyacá');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'CAL', '', 0, 'CAL', 'Caldas');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'CAU', '', 0, 'CAU', 'Cauca');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'CUN', '', 0, 'CUN', 'Cundinamarca');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'HUI', '', 0, 'HUI', 'Huila');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'LAG', '', 0, 'LAG', 'La Guajira');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'MET', '', 0, 'MET', 'Meta');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'NAR', '', 0, 'NAR', 'Nariño');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'NDS', '', 0, 'NDS', 'Norte de Santander');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'SAN', '', 0, 'SAN', 'Santander');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'SUC', '', 0, 'SUC', 'Sucre');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'TOL', '', 0, 'TOL', 'Tolima');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'VAC', '', 0, 'VAC', 'Valle del Cauca');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'RIS', '', 0, 'RIS', 'Risalda');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'ATL', '', 0, 'ATL', 'Atlántico');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'COR', '', 0, 'COR', 'Córdoba');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'SAP', '', 0, 'SAP', 'San Andrés, Providencia y Santa Catalina');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'ARA', '', 0, 'ARA', 'Arauca');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'CAS', '', 0, 'CAS', 'Casanare');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'AMA', '', 0, 'AMA', 'Amazonas');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'CAQ', '', 0, 'CAQ', 'Caquetá');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'CHO', '', 0, 'CHO', 'Chocó');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'GUA', '', 0, 'GUA', 'Guainía');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'GUV', '', 0, 'GUV', 'Guaviare');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'PUT', '', 0, 'PUT', 'Putumayo');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'QUI', '', 0, 'QUI', 'Quindío');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'VAU', '', 0, 'VAU', 'Vaupés');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'BOG', '', 0, 'BOG', 'Bogotá');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'VID', '', 0, 'VID', 'Vichada');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'CES', '', 0, 'CES', 'Cesar');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7001, 'MAG', '', 0, 'MAG', 'Magdalena');


-- France Departements (id country=1)
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values ( 1, '971','97105',3,'GUADELOUPE','Guadeloupe');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values ( 2, '972','97209',3,'MARTINIQUE','Martinique');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values ( 3, '973','97302',3,'GUYANE','Guyane');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values ( 4, '974','97411',3,'REUNION','Réunion');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values ( 6, '976','97601',3,'MAYOTTE','Mayotte');

insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (84,'01','01053',5,'AIN','Ain');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (32,'02','02408',5,'AISNE','Aisne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (84,'03','03190',5,'ALLIER','Allier');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'04','04070',4,'ALPES-DE-HAUTE-PROVENCE','Alpes-de-Haute-Provence');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'05','05061',4,'HAUTES-ALPES','Hautes-Alpes');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'06','06088',4,'ALPES-MARITIMES','Alpes-Maritimes');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (84,'07','07186',5,'ARDECHE','Ardèche');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (44,'08','08105',4,'ARDENNES','Ardennes');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (76,'09','09122',5,'ARIEGE','Ariège');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (44,'10','10387',5,'AUBE','Aube');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (76,'11','11069',5,'AUDE','Aude');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (76,'12','12202',5,'AVEYRON','Aveyron');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'13','13055',4,'BOUCHES-DU-RHONE','Bouches-du-Rhône');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (28,'14','14118',2,'CALVADOS','Calvados');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (84,'15','15014',2,'CANTAL','Cantal');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (75,'16','16015',3,'CHARENTE','Charente');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (75,'17','17300',3,'CHARENTE-MARITIME','Charente-Maritime');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (24,'18','18033',2,'CHER','Cher');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (75,'19','19272',3,'CORREZE','Corrèze');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (94,'2A','2A004',3,'CORSE-DU-SUD','Corse-du-Sud');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (94,'2B','2B033',3,'HAUTE-CORSE','Haute-Corse');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (27,'21','21231',3,'COTE-D OR','Côte-d Or');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (53,'22','22278',4,'COTES-D ARMOR','Côtes-d Armor');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (75,'23','23096',3,'CREUSE','Creuse');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (75,'24','24322',3,'DORDOGNE','Dordogne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (27,'25','25056',2,'DOUBS','Doubs');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (84,'26','26362',3,'DROME','Drôme');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (28,'27','27229',5,'EURE','Eure');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (24,'28','28085',1,'EURE-ET-LOIR','Eure-et-Loir');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (53,'29','29232',2,'FINISTERE','Finistère');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (76,'30','30189',2,'GARD','Gard');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (76,'31','31555',3,'HAUTE-GARONNE','Haute-Garonne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (76,'32','32013',2,'GERS','Gers');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (75,'33','33063',3,'GIRONDE','Gironde');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (76,'34','34172',5,'HERAULT','Hérault');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (53,'35','35238',1,'ILLE-ET-VILAINE','Ille-et-Vilaine');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (24,'36','36044',5,'INDRE','Indre');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (24,'37','37261',1,'INDRE-ET-LOIRE','Indre-et-Loire');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (84,'38','38185',5,'ISERE','Isère');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (27,'39','39300',2,'JURA','Jura');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (75,'40','40192',4,'LANDES','Landes');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (24,'41','41018',0,'LOIR-ET-CHER','Loir-et-Cher');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (84,'42','42218',3,'LOIRE','Loire');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (84,'43','43157',3,'HAUTE-LOIRE','Haute-Loire');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (52,'44','44109',3,'LOIRE-ATLANTIQUE','Loire-Atlantique');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (24,'45','45234',2,'LOIRET','Loiret');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (76,'46','46042',2,'LOT','Lot');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (75,'47','47001',0,'LOT-ET-GARONNE','Lot-et-Garonne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (76,'48','48095',3,'LOZERE','Lozère');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (52,'49','49007',0,'MAINE-ET-LOIRE','Maine-et-Loire');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (28,'50','50502',3,'MANCHE','Manche');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (44,'51','51108',3,'MARNE','Marne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (44,'52','52121',3,'HAUTE-MARNE','Haute-Marne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (52,'53','53130',3,'MAYENNE','Mayenne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (44,'54','54395',0,'MEURTHE-ET-MOSELLE','Meurthe-et-Moselle');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (44,'55','55029',3,'MEUSE','Meuse');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (53,'56','56260',2,'MORBIHAN','Morbihan');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (44,'57','57463',3,'MOSELLE','Moselle');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (27,'58','58194',3,'NIEVRE','Nièvre');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (32,'59','59350',2,'NORD','Nord');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (32,'60','60057',5,'OISE','Oise');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (28,'61','61001',5,'ORNE','Orne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (32,'62','62041',2,'PAS-DE-CALAIS','Pas-de-Calais');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (84,'63','63113',2,'PUY-DE-DOME','Puy-de-Dôme');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (75,'64','64445',4,'PYRENEES-ATLANTIQUES','Pyrénées-Atlantiques');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (76,'65','65440',4,'HAUTES-PYRENEES','Hautes-Pyrénées');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (76,'66','66136',4,'PYRENEES-ORIENTALES','Pyrénées-Orientales');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (44,'67','67482',2,'BAS-RHIN','Bas-Rhin');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (44,'68','68066',2,'HAUT-RHIN','Haut-Rhin');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (84,'69','69123',2,'RHONE','Rhône');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (27,'70','70550',3,'HAUTE-SAONE','Haute-Saône');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (27,'71','71270',0,'SAONE-ET-LOIRE','Saône-et-Loire');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (52,'72','72181',3,'SARTHE','Sarthe');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (84,'73','73065',3,'SAVOIE','Savoie');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (84,'74','74010',3,'HAUTE-SAVOIE','Haute-Savoie');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'75','75056',0,'PARIS','Paris');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (28,'76','76540',3,'SEINE-MARITIME','Seine-Maritime');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'77','77288',0,'SEINE-ET-MARNE','Seine-et-Marne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'78','78646',4,'YVELINES','Yvelines');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (75,'79','79191',4,'DEUX-SEVRES','Deux-Sèvres');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (32,'80','80021',3,'SOMME','Somme');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (76,'81','81004',2,'TARN','Tarn');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (76,'82','82121',0,'TARN-ET-GARONNE','Tarn-et-Garonne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'83','83137',2,'VAR','Var');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (93,'84','84007',0,'VAUCLUSE','Vaucluse');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (52,'85','85191',3,'VENDEE','Vendée');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (75,'86','86194',3,'VIENNE','Vienne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (75,'87','87085',3,'HAUTE-VIENNE','Haute-Vienne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (44,'88','88160',4,'VOSGES','Vosges');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (27,'89','89024',5,'YONNE','Yonne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (27,'90','90010',0,'TERRITOIRE DE BELFORT','Territoire de Belfort');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'91','91228',5,'ESSONNE','Essonne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'92','92050',4,'HAUTS-DE-SEINE','Hauts-de-Seine');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'93','93008',3,'SEINE-SAINT-DENIS','Seine-Saint-Denis');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'94','94028',2,'VAL-DE-MARNE','Val-de-Marne');
insert into llx_c_departements (fk_region, code_departement,cheflieu,tncc,ncc,nom) values (11,'95','95500',2,'VAL-D OISE','Val-d Oise');


-- Germany States / Bundesländer (id country=5)
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (501, 'BW', 'BADEN-WÜRTTEMBERG', 'Baden-Württemberg'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (501, 'BY', 'BAYERN', 'Bayern');
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (501, 'BE', 'BERLIN', 'Berlin');
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (501, 'BB', 'BRANDENBURG', 'Brandenburg');
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (501, 'HB', 'BREMEN', 'Bremen');
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (501, 'HH', 'HAMBURG', 'Hamburg');
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (501, 'HE', 'HESSEN', 'Hessen'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (501, 'MV', 'MECKLENBURG-VORPOMMERN', 'Mecklenburg-Vorpommern'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (501, 'NI', 'NIEDERSACHSEN', 'Niedersachsen');
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (501, 'NW', 'NORDRHEIN-WESTFALEN', 'Nordrhein-Westfalen');
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (501, 'RP', 'RHEINLAND-PFALZ', 'Rheinland-Pfalz');
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (501, 'SL', 'SAARLAND', 'Saarland');
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (501, 'SN', 'SACHSEN', 'Sachsen');
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (501, 'ST', 'SACHSEN-ANHALT', 'Sachsen-Anhalt'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (501, 'SH', 'SCHLESWIG-HOLSTEIN', 'Schleswig-Holstein'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (501, 'TH', 'THÜRINGEN', 'Thüringen');


-- Honduras Departamentos (id country=114)
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (11401, 'AT', '', 0, 'AT', 'Atlántida');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (11401, 'CH', '', 0, 'CH', 'Choluteca');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (11401, 'CL', '', 0, 'CL', 'Colón');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (11401, 'CM', '', 0, 'CM', 'Comayagua');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (11401, 'CO', '', 0, 'CO', 'Copán');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (11401, 'CR', '', 0, 'CR', 'Cortés');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (11401, 'EP', '', 0, 'EP', 'El Paraíso');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (11401, 'FM', '', 0, 'FM', 'Francisco Morazán');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (11401, 'GD', '', 0, 'GD', 'Gracias a Dios');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (11401, 'IN', '', 0, 'IN', 'Intibucá');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (11401, 'IB', '', 0, 'IB', 'Islas de la Bahía');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (11401, 'LP', '', 0, 'LP', 'La Paz');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (11401, 'LM', '', 0, 'LM', 'Lempira');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (11401, 'OC', '', 0, 'OC', 'Ocotepeque');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (11401, 'OL', '', 0, 'OL', 'Olancho');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (11401, 'SB', '', 0, 'SB', 'Santa Bárbara');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (11401, 'VL', '', 0, 'VL', 'Valle');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (11401, 'YO', '', 0, 'YO', 'Yoro');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (11401, 'DC', '', 0, 'DC', 'Distrito Central');


-- Hungary Provinces (rowid country=18)
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (180100, 'HU-BU', 'HU101', NULL, NULL, 'Budapest');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (180100, 'HU-PE', 'HU102', NULL, NULL, 'Pest');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (182100, 'HU-FE', 'HU211', NULL, NULL, 'Fejér');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (182100, 'HU-KE', 'HU212', NULL, NULL, 'Komárom-Esztergom');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (182100, 'HU-VE', 'HU213', NULL, NULL, 'Veszprém');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (182200, 'HU-GS', 'HU221', NULL, NULL, 'Győr-Moson-Sopron');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (182200, 'HU-VA', 'HU222', NULL, NULL, 'Vas');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (182200, 'HU-ZA', 'HU223', NULL, NULL, 'Zala');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (182300, 'HU-BA', 'HU231', NULL, NULL, 'Baranya');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (182300, 'HU-SO', 'HU232', NULL, NULL, 'Somogy');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (182300, 'HU-TO', 'HU233', NULL, NULL, 'Tolna');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (183100, 'HU-BZ', 'HU311', NULL, NULL, 'Borsod-Abaúj-Zemplén');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (183100, 'HU-HE', 'HU312', NULL, NULL, 'Heves');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (183100, 'HU-NO', 'HU313', NULL, NULL, 'Nógrád');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (183200, 'HU-HB', 'HU321', NULL, NULL, 'Hajdú-Bihar');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (183200, 'HU-JN', 'HU322', NULL, NULL, 'Jász-Nagykun-Szolnok');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (183200, 'HU-SZ', 'HU323', NULL, NULL, 'Szabolcs-Szatmár-Bereg');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (183300, 'HU-BK', 'HU331', NULL, NULL, 'Bács-Kiskun');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (183300, 'HU-BE', 'HU332', NULL, NULL, 'Békés');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (183300, 'HU-CS', 'HU333', NULL, NULL, 'Csongrád');


-- Provinces Italy (id=3)
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('AG',315,NULL,NULL,NULL,'AGRIGENTO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('AL',312,NULL,NULL,NULL,'ALESSANDRIA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('AN',310,NULL,NULL,NULL,'ANCONA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('AO',319,NULL,NULL,NULL,'AOSTA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('AR',316,NULL,NULL,NULL,'AREZZO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('AP',310,NULL,NULL,NULL,'ASCOLI PICENO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('AT',312,NULL,NULL,NULL,'ASTI');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('AV',304,NULL,NULL,NULL,'AVELLINO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('BA',313,NULL,NULL,NULL,'BARI');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('BT',313,NULL,NULL,NULL,'BARLETTA-ANDRIA-TRANI');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('BL',320,NULL,NULL,NULL,'BELLUNO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('BN',304,NULL,NULL,NULL,'BENEVENTO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('BG',309,NULL,NULL,NULL,'BERGAMO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('BI',312,NULL,NULL,NULL,'BIELLA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('BO',305,NULL,NULL,NULL,'BOLOGNA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('BZ',317,NULL,NULL,NULL,'BOLZANO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('BS',309,NULL,NULL,NULL,'BRESCIA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('BR',313,NULL,NULL,NULL,'BRINDISI');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('CA',314,NULL,NULL,NULL,'CAGLIARI');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('CL',315,NULL,NULL,NULL,'CALTANISSETTA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('CB',311,NULL,NULL,NULL,'CAMPOBASSO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('CI',314,NULL,NULL,NULL,'CARBONIA-IGLESIAS');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('CE',304,NULL,NULL,NULL,'CASERTA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('CT',315,NULL,NULL,NULL,'CATANIA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('CZ',303,NULL,NULL,NULL,'CATANZARO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('CH',301,NULL,NULL,NULL,'CHIETI');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('CO',309,NULL,NULL,NULL,'COMO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('CS',303,NULL,NULL,NULL,'COSENZA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('CR',309,NULL,NULL,NULL,'CREMONA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('KR',303,NULL,NULL,NULL,'CROTONE');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('CN',312,NULL,NULL,NULL,'CUNEO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('EN',315,NULL,NULL,NULL,'ENNA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('FM',310,NULL,NULL,NULL,'FERMO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('FE',305,NULL,NULL,NULL,'FERRARA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('FI',316,NULL,NULL,NULL,'FIRENZE');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('FG',313,NULL,NULL,NULL,'FOGGIA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('FC',305,NULL,NULL,NULL,'FORLI-CESENA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('FR',307,NULL,NULL,NULL,'FROSINONE');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('GE',308,NULL,NULL,NULL,'GENOVA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('GO',306,NULL,NULL,NULL,'GORIZIA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('GR',316,NULL,NULL,NULL,'GROSSETO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('IM',308,NULL,NULL,NULL,'IMPERIA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('IS',311,NULL,NULL,NULL,'ISERNIA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('SP',308,NULL,NULL,NULL,'LA SPEZIA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('AQ',301,NULL,NULL,NULL,'L AQUILA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('LT',307,NULL,NULL,NULL,'LATINA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('LE',313,NULL,NULL,NULL,'LECCE');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('LC',309,NULL,NULL,NULL,'LECCO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('LI',314,NULL,NULL,NULL,'LIVORNO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('LO',309,NULL,NULL,NULL,'LODI');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('LU',316,NULL,NULL,NULL,'LUCCA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('MC',310,NULL,NULL,NULL,'MACERATA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('MN',309,NULL,NULL,NULL,'MANTOVA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('MS',316,NULL,NULL,NULL,'MASSA-CARRARA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('MT',302,NULL,NULL,NULL,'MATERA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('VS',314,NULL,NULL,NULL,'MEDIO CAMPIDANO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('ME',315,NULL,NULL,NULL,'MESSINA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('MI',309,NULL,NULL,NULL,'MILANO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('MB',309,NULL,NULL,NULL,'MONZA e BRIANZA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('MO',305,NULL,NULL,NULL,'MODENA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('NA',304,NULL,NULL,NULL,'NAPOLI');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('NO',312,NULL,NULL,NULL,'NOVARA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('NU',314,NULL,NULL,NULL,'NUORO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('OG',314,NULL,NULL,NULL,'OGLIASTRA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('OT',314,NULL,NULL,NULL,'OLBIA-TEMPIO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('OR',314,NULL,NULL,NULL,'ORISTANO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('PD',320,NULL,NULL,NULL,'PADOVA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('PA',315,NULL,NULL,NULL,'PALERMO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('PR',305,NULL,NULL,NULL,'PARMA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('PV',309,NULL,NULL,NULL,'PAVIA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('PG',318,NULL,NULL,NULL,'PERUGIA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('PU',310,NULL,NULL,NULL,'PESARO e URBINO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('PE',301,NULL,NULL,NULL,'PESCARA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('PC',305,NULL,NULL,NULL,'PIACENZA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('PI',316,NULL,NULL,NULL,'PISA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('PT',316,NULL,NULL,NULL,'PISTOIA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('PN',306,NULL,NULL,NULL,'PORDENONE');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('PZ',302,NULL,NULL,NULL,'POTENZA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('PO',316,NULL,NULL,NULL,'PRATO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('RG',315,NULL,NULL,NULL,'RAGUSA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('RA',305,NULL,NULL,NULL,'RAVENNA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('RC',303,NULL,NULL,NULL,'REGGIO CALABRIA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('RE',305,NULL,NULL,NULL,'REGGIO NELL EMILIA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('RI',307,NULL,NULL,NULL,'RIETI');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('RN',305,NULL,NULL,NULL,'RIMINI');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('RM',307,NULL,NULL,NULL,'ROMA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('RO',320,NULL,NULL,NULL,'ROVIGO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('SA',304,NULL,NULL,NULL,'SALERNO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('SS',314,NULL,NULL,NULL,'SASSARI');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('SV',308,NULL,NULL,NULL,'SAVONA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('SI',316,NULL,NULL,NULL,'SIENA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('SR',315,NULL,NULL,NULL,'SIRACUSA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('SO',309,NULL,NULL,NULL,'SONDRIO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('TA',313,NULL,NULL,NULL,'TARANTO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('TE',301,NULL,NULL,NULL,'TERAMO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('TR',318,NULL,NULL,NULL,'TERNI');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('TO',312,NULL,NULL,NULL,'TORINO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('TP',315,NULL,NULL,NULL,'TRAPANI');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('TN',317,NULL,NULL,NULL,'TRENTO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('TV',320,NULL,NULL,NULL,'TREVISO');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('TS',306,NULL,NULL,NULL,'TRIESTE');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('UD',306,NULL,NULL,NULL,'UDINE');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('VA',309,NULL,NULL,NULL,'VARESE');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('VE',320,NULL,NULL,NULL,'VENEZIA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('VB',312,NULL,NULL,NULL,'VERBANO-CUSIO-OSSOLA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('VC',312,NULL,NULL,NULL,'VERCELLI');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('VR',320,NULL,NULL,NULL,'VERONA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('VV',303,NULL,NULL,NULL,'VIBO VALENTIA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('VI',320,NULL,NULL,NULL,'VICENZA');
insert into llx_c_departements (code_departement,fk_region,cheflieu,tncc,ncc,nom) values ('VT',307,NULL,NULL,NULL,'VITERBO');


-- Luxembourg Cantons (id country=140)
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (14001, 'LU0001', '', 0, '', 'Clervaux');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (14001, 'LU0002', '', 0, '', 'Diekirch');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (14001, 'LU0003', '', 0, '', 'Redange');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (14001, 'LU0004', '', 0, '', 'Vianden');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (14001, 'LU0005', '', 0, '', 'Wiltz');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (14002, 'LU0006', '', 0, '', 'Echternach');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (14002, 'LU0007', '', 0, '', 'Grevenmacher');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (14002, 'LU0008', '', 0, '', 'Remich');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (14003, 'LU0009', '', 0, '', 'Capellen');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (14003, 'LU0010', '', 0, '', 'Esch-sur-Alzette');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (14003, 'LU0011', '', 0, '', 'Luxembourg');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (14003, 'LU0012', '', 0, '', 'Mersch');


-- Netherlands/Nederland Provinces (id country=17)
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1701, 'GR', NULL, NULL, NULL, 'Groningen');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1701, 'FR', NULL, NULL, NULL, 'Friesland');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1701, 'DR', NULL, NULL, NULL, 'Drenthe');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1701, 'OV', NULL, NULL, NULL, 'Overijssel');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1701, 'GD', NULL, NULL, NULL, 'Gelderland');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1701, 'FL', NULL, NULL, NULL, 'Flevoland');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1701, 'UT', NULL, NULL, NULL, 'Utrecht');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1701, 'NH', NULL, NULL, NULL, 'Noord-Holland');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1701, 'ZH', NULL, NULL, NULL, 'Zuid-Holland');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1701, 'ZL', NULL, NULL, NULL, 'Zeeland');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1701, 'NB', NULL, NULL, NULL, 'Noord-Brabant');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1701, 'LB', NULL, NULL, NULL, 'Limburg');


-- Provinces Maroc - Moroco (id country=12)
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA', 1209, '', 0, '', 'Province de Benslimane', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA1', 1209, '', 0, '', 'Province de Berrechid', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA2', 1209, '', 0, '', 'Province de Khouribga', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA3', 1209, '', 0, '', 'Province de Settat', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA4', 1210, '', 0, '', 'Province d''El Jadida', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA5', 1210, '', 0, '', 'Province de Safi', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA6', 1210, '', 0, '', 'Province de Sidi Bennour', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA7', 1210, '', 0, '', 'Province de Youssoufia', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA6B', 1205, '', 0, '', 'Préfecture de Fès', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA7B', 1205, '', 0, '', 'Province de Boulemane', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA8', 1205, '', 0, '', 'Province de Moulay Yacoub', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA9', 1205, '', 0, '', 'Province de Sefrou', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA8A', 1202, '', 0, '', 'Province de Kénitra', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA9A', 1202, '', 0, '', 'Province de Sidi Kacem', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA10', 1202, '', 0, '', 'Province de Sidi Slimane', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA11', 1208, '', 0, '', 'Préfecture de Casablanca', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA12', 1208, '', 0, '', 'Préfecture de Mohammédia', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA13', 1208, '', 0, '', 'Province de Médiouna', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA14', 1208, '', 0, '', 'Province de Nouaceur', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA15', 1214, '', 0, '', 'Province d''Assa-Zag', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA16', 1214, '', 0, '', 'Province d''Es-Semara', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA17A', 1214, '', 0, '', 'Province de Guelmim', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA18', 1214, '', 0, '', 'Province de Tata', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA19', 1214, '', 0, '', 'Province de Tan-Tan', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA15', 1215, '', 0, '', 'Province de Boujdour', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA16', 1215, '', 0, '', 'Province de Lâayoune', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA17', 1215, '', 0, '', 'Province de Tarfaya', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA18', 1211, '', 0, '', 'Préfecture de Marrakech', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA19', 1211, '', 0, '', 'Province d''Al Haouz', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA20', 1211, '', 0, '', 'Province de Chichaoua', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA21', 1211, '', 0, '', 'Province d''El Kelâa des Sraghna', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA22', 1211, '', 0, '', 'Province d''Essaouira', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA23', 1211, '', 0, '', 'Province de Rehamna', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA24', 1206, '', 0, '', 'Préfecture de Meknès', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA25', 1206, '', 0, '', 'Province d’El Hajeb', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA26', 1206, '', 0, '', 'Province d''Errachidia', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA27', 1206, '', 0, '', 'Province d’Ifrane', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA28', 1206, '', 0, '', 'Province de Khénifra', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA29', 1206, '', 0, '', 'Province de Midelt', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA30', 1204, '', 0, '', 'Préfecture d''Oujda-Angad', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA31', 1204, '', 0, '', 'Province de Berkane', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA32', 1204, '', 0, '', 'Province de Driouch', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA33', 1204, '', 0, '', 'Province de Figuig', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA34', 1204, '', 0, '', 'Province de Jerada', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA35', 1204, '', 0, '', 'Province de Nador', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA36', 1204, '', 0, '', 'Province de Taourirt', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA37', 1216, '', 0, '', 'Province d''Aousserd', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA38', 1216, '', 0, '', 'Province d''Oued Ed-Dahab', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA39', 1207, '', 0, '', 'Préfecture de Rabat', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA40', 1207, '', 0, '', 'Préfecture de Skhirat-Témara', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA41', 1207, '', 0, '', 'Préfecture de Salé', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA42', 1207, '', 0, '', 'Province de Khémisset', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA43', 1213, '', 0, '', 'Préfecture d''Agadir Ida-Outanane', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA44', 1213, '', 0, '', 'Préfecture d''Inezgane-Aït Melloul', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA45', 1213, '', 0, '', 'Province de Chtouka-Aït Baha', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA46', 1213, '', 0, '', 'Province d''Ouarzazate', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA47', 1213, '', 0, '', 'Province de Sidi Ifni', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA48', 1213, '', 0, '', 'Province de Taroudant', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA49', 1213, '', 0, '', 'Province de Tinghir', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA50', 1213, '', 0, '', 'Province de Tiznit', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA51', 1213, '', 0, '', 'Province de Zagora', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA52', 1212, '', 0, '', 'Province d''Azilal', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA53', 1212, '', 0, '', 'Province de Beni Mellal', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA54', 1212, '', 0, '', 'Province de Fquih Ben Salah', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA55', 1201, '', 0, '', 'Préfecture de M''diq-Fnideq', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA56', 1201, '', 0, '', 'Préfecture de Tanger-Asilah', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA57', 1201, '', 0, '', 'Province de Chefchaouen', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA58', 1201, '', 0, '', 'Province de Fahs-Anjra', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA59', 1201, '', 0, '', 'Province de Larache', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA60', 1201, '', 0, '', 'Province d''Ouezzane', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA61', 1201, '', 0, '', 'Province de Tétouan', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA62', 1203, '', 0, '', 'Province de Guercif', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA63', 1203, '', 0, '', 'Province d''Al Hoceïma', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA64', 1203, '', 0, '', 'Province de Taounate', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA65', 1203, '', 0, '', 'Province de Taza', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA6A', 1205, '', 0, '', 'Préfecture de Fès', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA7A', 1205, '', 0, '', 'Province de Boulemane', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA15A', 1214, '', 0, '', 'Province d''Assa-Zag', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA16A', 1214, '', 0, '', 'Province d''Es-Semara', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA18A', 1211, '', 0, '', 'Préfecture de Marrakech', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA19A', 1214, '', 0, '', 'Province de Tan-Tan', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('MA19B', 1214, '', 0, '', 'Province de Tan-Tan', 1);


-- Romania Provinces (id country=188)
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'AB', '', 0, '', 'Alba');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'AR', '', 0, '', 'Arad');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'AG', '', 0, '', 'Argeș');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'BC', '', 0, '', 'Bacău');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'BH', '', 0, '', 'Bihor');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'BN', '', 0, '', 'Bistrița-Năsăud');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'BT', '', 0, '', 'Botoșani');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'BV', '', 0, '', 'Brașov');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'BR', '', 0, '', 'Brăila');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'BU', '', 0, '', 'Bucuresti');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'BZ', '', 0, '', 'Buzău');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'CL', '', 0, '', 'Călărași');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'CS', '', 0, '', 'Caraș-Severin');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'CJ', '', 0, '', 'Cluj');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'CT', '', 0, '', 'Constanța');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'CV', '', 0, '', 'Covasna');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'DB', '', 0, '', 'Dâmbovița');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'DJ', '', 0, '', 'Dolj');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'GL', '', 0, '', 'Galați');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'GR', '', 0, '', 'Giurgiu');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'GJ', '', 0, '', 'Gorj');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'HR', '', 0, '', 'Harghita');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'HD', '', 0, '', 'Hunedoara');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'IL', '', 0, '', 'Ialomița');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'IS', '', 0, '', 'Iași');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'IF', '', 0, '', 'Ilfov');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'MM', '', 0, '', 'Maramureș');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'MH', '', 0, '', 'Mehedinți');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'MS', '', 0, '', 'Mureș');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'NT', '', 0, '', 'Neamț');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'OT', '', 0, '', 'Olt');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'PH', '', 0, '', 'Prahova');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'SM', '', 0, '', 'Satu Mare');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'SJ', '', 0, '', 'Sălaj');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'SB', '', 0, '', 'Sibiu');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'SV', '', 0, '', 'Suceava');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'TR', '', 0, '', 'Teleorman');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'TM', '', 0, '', 'Timiș');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'TL', '', 0, '', 'Tulcea');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'VS', '', 0, '', 'Vaslui');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'VL', '', 0, '', 'Vâlcea');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (18801, 'VN', '', 0, '', 'Vrancea');


-- Provinces Tunisia (id country=10)
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN01', 1001, '', 0, '', 'Ariana', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN02', 1001, '', 0, '', 'Béja', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN03', 1001, '', 0, '', 'Ben Arous', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN04', 1001, '', 0, '', 'Bizerte', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN05', 1001, '', 0, '', 'Gabès', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN06', 1001, '', 0, '', 'Gafsa', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN07', 1001, '', 0, '', 'Jendouba', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN08', 1001, '', 0, '', 'Kairouan', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN09', 1001, '', 0, '', 'Kasserine', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN10', 1001, '', 0, '', 'Kébili', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN11', 1001, '', 0, '', 'La Manouba', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN12', 1001, '', 0, '', 'Le Kef', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN13', 1001, '', 0, '', 'Mahdia', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN14', 1001, '', 0, '', 'Médenine', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN15', 1001, '', 0, '', 'Monastir', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN16', 1001, '', 0, '', 'Nabeul', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN17', 1001, '', 0, '', 'Sfax', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN18', 1001, '', 0, '', 'Sidi Bouzid', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN19', 1001, '', 0, '', 'Siliana', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN20', 1001, '', 0, '', 'Sousse', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN21', 1001, '', 0, '', 'Tataouine', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN22', 1001, '', 0, '', 'Tozeur', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN23', 1001, '', 0, '', 'Tunis', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES('TN24', 1001, '', 0, '', 'Zaghouan', 1);


-- Provinces Bolivia (id country=52)
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('001', 5201, '', 0, '', 'Belisario Boeto', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('002', 5201, '', 0, '', 'Hernando Siles', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('003', 5201, '', 0, '', 'Jaime Zudáñez', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('004', 5201, '', 0, '', 'Juana Azurduy de Padilla', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('005', 5201, '', 0, '', 'Luis Calvo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('006', 5201, '', 0, '', 'Nor Cinti', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('007', 5201, '', 0, '', 'Oropeza', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('008', 5201, '', 0, '', 'Sud Cinti', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('009', 5201, '', 0, '', 'Tomina', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('010', 5201, '', 0, '', 'Yamparáez', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('011', 5202, '', 0, '', 'Abel Iturralde', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('012', 5202, '', 0, '', 'Aroma', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('013', 5202, '', 0, '', 'Bautista Saavedra', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('014', 5202, '', 0, '', 'Caranavi', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('015', 5202, '', 0, '', 'Eliodoro Camacho', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('016', 5202, '', 0, '', 'Franz Tamayo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('017', 5202, '', 0, '', 'Gualberto Villarroel', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('018', 5202, '', 0, '', 'Ingaví', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('019', 5202, '', 0, '', 'Inquisivi', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('020', 5202, '', 0, '', 'José Ramón Loayza', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('021', 5202, '', 0, '', 'Larecaja', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('022', 5202, '', 0, '', 'Los Andes (Bolivia)', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('023', 5202, '', 0, '', 'Manco Kapac', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('024', 5202, '', 0, '', 'Muñecas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('025', 5202, '', 0, '', 'Nor Yungas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('026', 5202, '', 0, '', 'Omasuyos', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('027', 5202, '', 0, '', 'Pacajes', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('028', 5202, '', 0, '', 'Pedro Domingo Murillo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('029', 5202, '', 0, '', 'Sud Yungas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('030', 5202, '', 0, '', 'General José Manuel Pando', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('031', 5203, '', 0, '', 'Arani', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('032', 5203, '', 0, '', 'Arque', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('033', 5203, '', 0, '', 'Ayopaya', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('034', 5203, '', 0, '', 'Bolívar (Bolivia)', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('035', 5203, '', 0, '', 'Campero', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('036', 5203, '', 0, '', 'Capinota', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('037', 5203, '', 0, '', 'Cercado (Cochabamba)', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('038', 5203, '', 0, '', 'Esteban Arze', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('039', 5203, '', 0, '', 'Germán Jordán', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('040', 5203, '', 0, '', 'José Carrasco', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('041', 5203, '', 0, '', 'Mizque', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('042', 5203, '', 0, '', 'Punata', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('043', 5203, '', 0, '', 'Quillacollo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('044', 5203, '', 0, '', 'Tapacarí', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('045', 5203, '', 0, '', 'Tiraque', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('046', 5203, '', 0, '', 'Chapare', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('047', 5204, '', 0, '', 'Carangas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('048', 5204, '', 0, '', 'Cercado (Oruro)', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('049', 5204, '', 0, '', 'Eduardo Avaroa', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('050', 5204, '', 0, '', 'Ladislao Cabrera', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('051', 5204, '', 0, '', 'Litoral de Atacama', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('052', 5204, '', 0, '', 'Mejillones', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('053', 5204, '', 0, '', 'Nor Carangas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('054', 5204, '', 0, '', 'Pantaleón Dalence', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('055', 5204, '', 0, '', 'Poopó', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('056', 5204, '', 0, '', 'Sabaya', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('057', 5204, '', 0, '', 'Sajama', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('058', 5204, '', 0, '', 'San Pedro de Totora', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('059', 5204, '', 0, '', 'Saucarí', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('060', 5204, '', 0, '', 'Sebastián Pagador', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('061', 5204, '', 0, '', 'Sud Carangas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('062', 5204, '', 0, '', 'Tomás Barrón', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('063', 5205, '', 0, '', 'Alonso de Ibáñez', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('064', 5205, '', 0, '', 'Antonio Quijarro', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('065', 5205, '', 0, '', 'Bernardino Bilbao', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('066', 5205, '', 0, '', 'Charcas (Potosí)', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('067', 5205, '', 0, '', 'Chayanta', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('068', 5205, '', 0, '', 'Cornelio Saavedra', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('069', 5205, '', 0, '', 'Daniel Campos', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('070', 5205, '', 0, '', 'Enrique Baldivieso', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('071', 5205, '', 0, '', 'José María Linares', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('072', 5205, '', 0, '', 'Modesto Omiste', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('073', 5205, '', 0, '', 'Nor Chichas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('074', 5205, '', 0, '', 'Nor Lípez', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('075', 5205, '', 0, '', 'Rafael Bustillo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('076', 5205, '', 0, '', 'Sud Chichas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('077', 5205, '', 0, '', 'Sud Lípez', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('078', 5205, '', 0, '', 'Tomás Frías', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('079', 5206, '', 0, '', 'Aniceto Arce', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('080', 5206, '', 0, '', 'Burdet O''Connor', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('081', 5206, '', 0, '', 'Cercado (Tarija)', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('082', 5206, '', 0, '', 'Eustaquio Méndez', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('083', 5206, '', 0, '', 'José María Avilés', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('084', 5206, '', 0, '', 'Gran Chaco', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('085', 5207, '', 0, '', 'Andrés Ibáñez', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('086', 5207, '', 0, '', 'Caballero', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('087', 5207, '', 0, '', 'Chiquitos', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('088', 5207, '', 0, '', 'Cordillera (Bolivia)', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('089', 5207, '', 0, '', 'Florida', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('090', 5207, '', 0, '', 'Germán Busch', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('091', 5207, '', 0, '', 'Guarayos', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('092', 5207, '', 0, '', 'Ichilo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('093', 5207, '', 0, '', 'Obispo Santistevan', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('094', 5207, '', 0, '', 'Sara', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('095', 5207, '', 0, '', 'Vallegrande', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('096', 5207, '', 0, '', 'Velasco', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('097', 5207, '', 0, '', 'Warnes', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('098', 5207, '', 0, '', 'Ángel Sandóval', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('099', 5207, '', 0, '', 'Ñuflo de Chaves', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('100', 5208, '', 0, '', 'Cercado (Beni)', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('101', 5208, '', 0, '', 'Iténez', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('102', 5208, '', 0, '', 'Mamoré', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('103', 5208, '', 0, '', 'Marbán', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('104', 5208, '', 0, '', 'Moxos', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('105', 5208, '', 0, '', 'Vaca Díez', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('106', 5208, '', 0, '', 'Yacuma', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('107', 5208, '', 0, '', 'General José Ballivián Segurola', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('108', 5209, '', 0, '', 'Abuná', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('109', 5209, '', 0, '', 'Madre de Dios', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('110', 5209, '', 0, '', 'Manuripi', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('111', 5209, '', 0, '', 'Nicolás Suárez', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('112', 5209, '', 0, '', 'General Federico Román', 1);


-- Provinces Spain (id country=4) in order of province (for logical pick list)
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('VI', '419', '01', 19, 'ALAVA', 'Álava');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('AB', '404', '02', 4,  'ALBACETE', 'Albacete');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('A',  '411', '03', 11, 'ALICANTE', 'Alicante');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('AL', '401', '04', 1,  'ALMERIA', 'Almería');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('O',  '418', '33', 18, 'ASTURIAS', 'Asturias');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('AV', '403', '05', 3,  'AVILA', 'Ávila');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('BA', '412', '06', 12, 'BADAJOZ', 'Badajoz');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('B',  '406', '08', 6,  'BARCELONA', 'Barcelona');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('BU', '403', '09', 8,  'BURGOS', 'Burgos');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('CC', '412', '10', 12, 'CACERES', 'Cáceres');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('CA', '401', '11', 1,  'CADIZ', 'Cádiz');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('S',  '410', '39', 10, 'CANTABRIA', 'Cantabria');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('CS', '411', '12', 11, 'CASTELLON', 'Castellón');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('CE', '407', '51', 7,  'CEUTA', 'Ceuta');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('CR', '404', '13', 4,  'CIUDAD REAL', 'Ciudad Real');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('CO', '401', '14', 1,  'CORDOBA', 'Córdoba');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('CU', '404', '16', 4,  'CUENCA', 'Cuenca');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('GI', '406', '17', 6,  'GERONA', 'Gerona');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('GR', '401', '18', 1,  'GRANADA', 'Granada');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('GU', '404', '19', 4,  'GUADALAJARA', 'Guadalajara');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('SS', '419', '20', 19, 'GUIPUZCOA', 'Guipúzcoa');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('H',  '401', '21', 1,  'HUELVA', 'Huelva');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('HU', '402', '22', 2,  'HUESCA', 'Huesca');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('PM', '414', '07', 14, 'ISLAS BALEARES', 'Islas Baleares');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('J',  '401', '23', 1,  'JAEN', 'Jaén');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('C',  '413', '15', 13, 'LA CORUÑA', 'La Coruña');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('LO', '415', '26', 15, 'LA RIOJA', 'La Rioja');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('GC', '405', '35', 5,  'LAS PALMAS', 'Las Palmas');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('LE', '403', '24', 3,  'LEON', 'León');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('L',  '406', '25', 6,  'LERIDA', 'Lérida');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('LU', '413', '27', 13, 'LUGO', 'Lugo');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('M',  '416', '28', 16, 'MADRID', 'Madrid');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('MA', '401', '29', 1,  'MALAGA', 'Málaga');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('ML', '409', '52', 9,  'MELILLA', 'Melilla');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('MU', '417', '30', 17, 'MURCIA', 'Murcia');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('NA', '408', '31', 8,  'NAVARRA', 'Navarra');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('OR', '413', '32', 13, 'ORENSE', 'Orense');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('P',  '403', '34', 3,  'PALENCIA', 'Palencia');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('PO', '413', '36', 13, 'PONTEVEDRA', 'Pontevedra');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('SA', '403', '37', 3,  'SALAMANCA', 'Salamanca');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('TF', '405', '38', 5,  'STA. CRUZ DE TENERIFE', 'Santa Cruz de Tenerife');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('SG', '403', '40', 3,  'SEGOVIA', 'Segovia');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('SE', '401', '41', 1,  'SEVILLA', 'Sevilla');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('SO', '403', '42', 3,  'SORIA', 'Soria');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('T',  '406', '43', 6,  'TARRAGONA', 'Tarragona');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('TE', '402', '44', 2,  'TERUEL', 'Teruel');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('TO', '404', '45', 5,  'TOLEDO', 'Toledo');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('V',  '411', '46', 11, 'VALENCIA', 'Valencia');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('VA', '403', '47', 3,  'VALLADOLID', 'Valladolid');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('BI', '419', '48', 19, 'VIZCAYA', 'Vizcaya');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('ZA', '403', '49', 3,  'ZAMORA', 'Zamora');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('Z',  '402', '50', 1,  'ZARAGOZA', 'Zaragoza');


-- Provinces Greece (id country=102)
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('66', 10201, '', 0, '', 'Αθήνα', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('67', 10205, '', 0, '', 'Δράμα', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('01', 10205, '', 0, '', 'Έβρος', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('02', 10205, '', 0, '', 'Θάσος', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('03', 10205, '', 0, '', 'Καβάλα', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('04', 10205, '', 0, '', 'Ξάνθη', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('05', 10205, '', 0, '', 'Ροδόπη', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('06', 10203, '', 0, '', 'Ημαθία', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('07', 10203, '', 0, '', 'Θεσσαλονίκη', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('08', 10203, '', 0, '', 'Κιλκίς', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('09', 10203, '', 0, '', 'Πέλλα', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('10', 10203, '', 0, '', 'Πιερία', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('11', 10203, '', 0, '', 'Σέρρες', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('12', 10203, '', 0, '', 'Χαλκιδική', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('13', 10206, '', 0, '', 'Άρτα', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('14', 10206, '', 0, '', 'Θεσπρωτία', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('15', 10206, '', 0, '', 'Ιωάννινα', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('16', 10206, '', 0, '', 'Πρέβεζα', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('17', 10213, '', 0, '', 'Γρεβενά', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('18', 10213, '', 0, '', 'Καστοριά', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('19', 10213, '', 0, '', 'Κοζάνη', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('20', 10213, '', 0, '', 'Φλώρινα', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('21', 10212, '', 0, '', 'Καρδίτσα', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('22', 10212, '', 0, '', 'Λάρισα', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('23', 10212, '', 0, '', 'Μαγνησία', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('24', 10212, '', 0, '', 'Τρίκαλα', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('25', 10212, '', 0, '', 'Σποράδες', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('26', 10212, '', 0, '', 'Βοιωτία', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('27', 10202, '', 0, '', 'Εύβοια', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('28', 10202, '', 0, '', 'Ευρυτανία', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('29', 10202, '', 0, '', 'Φθιώτιδα', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('30', 10202, '', 0, '', 'Φωκίδα', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('31', 10209, '', 0, '', 'Αργολίδα', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('32', 10209, '', 0, '', 'Αρκαδία', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('33', 10209, '', 0, '', 'Κορινθία', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('34', 10209, '', 0, '', 'Λακωνία', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('35', 10209, '', 0, '', 'Μεσσηνία', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('36', 10211, '', 0, '', 'Αιτωλοακαρνανία', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('37', 10211, '', 0, '', 'Αχαΐα', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('38', 10211, '', 0, '', 'Ηλεία', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('39', 10207, '', 0, '', 'Ζάκυνθος', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('40', 10207, '', 0, '', 'Κέρκυρα', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('41', 10207, '', 0, '', 'Κεφαλληνία', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('42', 10207, '', 0, '', 'Ιθάκη', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('43', 10207, '', 0, '', 'Λευκάδα', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('44', 10208, '', 0, '', 'Ικαρία', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('45', 10208, '', 0, '', 'Λέσβος', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('46', 10208, '', 0, '', 'Λήμνος', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('47', 10208, '', 0, '', 'Σάμος', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('48', 10208, '', 0, '', 'Χίος', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('49', 10210, '', 0, '', 'Άνδρος', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('50', 10210, '', 0, '', 'Θήρα', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('51', 10210, '', 0, '', 'Κάλυμνος', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('52', 10210, '', 0, '', 'Κάρπαθος', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('53', 10210, '', 0, '', 'Κέα-Κύθνος', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('54', 10210, '', 0, '', 'Κω', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('55', 10210, '', 0, '', 'Μήλος', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('56', 10210, '', 0, '', 'Μύκονος', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('57', 10210, '', 0, '', 'Νάξος', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('58', 10210, '', 0, '', 'Πάρος', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('59', 10210, '', 0, '', 'Ρόδος', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('60', 10210, '', 0, '', 'Σύρος', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('61', 10210, '', 0, '', 'Τήνος', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('62', 10204, '', 0, '', 'Ηράκλειο', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('63', 10204, '', 0, '', 'Λασίθι', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('64', 10204, '', 0, '', 'Ρέθυμνο', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('65', 10204, '', 0, '', 'Χανιά', 1);


-- Switzerland Cantons (id country=6)
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (601, 'AG','ARGOVIE','Argovie'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (601, 'AI','APPENZELL RHODES INTERIEURES','Appenzell Rhodes intérieures'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (601, 'AR','APPENZELL RHODES EXTERIEURES','Appenzell Rhodes extérieures'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (601, 'BE','BERNE','Berne'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (601, 'BL','BALE CAMPAGNE','Bâle Campagne'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (601, 'BS','BALE VILLE','Bâle Ville'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (601, 'FR','FRIBOURG','Fribourg'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (601, 'GE','GENEVE','Genève'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (601, 'GL','GLARIS','Glaris'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (601, 'GR','GRISONS','Grisons'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (601, 'JU','JURA','Jura'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (601, 'LU','LUCERNE','Lucerne'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (601, 'NE','NEUCHATEL','Neuchâtel'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (601, 'NW','NIDWALD','Nidwald'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (601, 'OW','OBWALD','Obwald'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (601, 'SG','SAINT-GALL','Saint-Gall'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (601, 'SH','SCHAFFHOUSE','Schaffhouse'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (601, 'SO','SOLEURE','Soleure'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (601, 'SZ','SCHWYZ','Schwyz'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (601, 'TG','THURGOVIE','Thurgovie'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (601, 'TI','TESSIN','Tessin'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (601, 'UR','URI','Uri'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (601, 'VD','VAUD','Vaud'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (601, 'VS','VALAIS','Valais'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (601, 'ZG','ZUG','Zug'); 
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (601, 'ZH','ZURICH','Zürich');


-- Provinces GB (id country=7)
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('701', 701, NULL, 0,NULL, 'Bedfordshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('702', 701, NULL, 0,NULL, 'Berkshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('703', 701, NULL, 0,NULL, 'Bristol, City of', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('704', 701, NULL, 0,NULL, 'Buckinghamshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('705', 701, NULL, 0,NULL, 'Cambridgeshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('706', 701, NULL, 0,NULL, 'Cheshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('707', 701, NULL, 0,NULL, 'Cleveland', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('708', 701, NULL, 0,NULL, 'Cornwall', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('709', 701, NULL, 0,NULL, 'Cumberland', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('710', 701, NULL, 0,NULL, 'Cumbria', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('711', 701, NULL, 0,NULL, 'Derbyshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('712', 701, NULL, 0,NULL, 'Devon', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('713', 701, NULL, 0,NULL, 'Dorset', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('714', 701, NULL, 0,NULL, 'Co. Durham', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('715', 701, NULL, 0,NULL, 'East Riding of Yorkshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('716', 701, NULL, 0,NULL, 'East Sussex', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('717', 701, NULL, 0,NULL, 'Essex', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('718', 701, NULL, 0,NULL, 'Gloucestershire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('719', 701, NULL, 0,NULL, 'Greater Manchester', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('720', 701, NULL, 0,NULL, 'Hampshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('721', 701, NULL, 0,NULL, 'Hertfordshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('722', 701, NULL, 0,NULL, 'Hereford and Worcester', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('723', 701, NULL, 0,NULL, 'Herefordshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('724', 701, NULL, 0,NULL, 'Huntingdonshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('725', 701, NULL, 0,NULL, 'Isle of Man', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('726', 701, NULL, 0,NULL, 'Isle of Wight', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('727', 701, NULL, 0,NULL, 'Jersey', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('728', 701, NULL, 0,NULL, 'Kent', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('729', 701, NULL, 0,NULL, 'Lancashire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('730', 701, NULL, 0,NULL, 'Leicestershire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('731', 701, NULL, 0,NULL, 'Lincolnshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('732', 701, NULL, 0,NULL, 'London - City of London', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('733', 701, NULL, 0,NULL, 'Merseyside', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('734', 701, NULL, 0,NULL, 'Middlesex', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('735', 701, NULL, 0,NULL, 'Norfolk', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('736', 701, NULL, 0,NULL, 'North Yorkshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('737', 701, NULL, 0,NULL, 'North Riding of Yorkshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('738', 701, NULL, 0,NULL, 'Northamptonshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('739', 701, NULL, 0,NULL, 'Northumberland', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('740', 701, NULL, 0,NULL, 'Nottinghamshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('741', 701, NULL, 0,NULL, 'Oxfordshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('742', 701, NULL, 0,NULL, 'Rutland', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('743', 701, NULL, 0,NULL, 'Shropshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('744', 701, NULL, 0,NULL, 'Somerset', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('745', 701, NULL, 0,NULL, 'Staffordshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('746', 701, NULL, 0,NULL, 'Suffolk', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('747', 701, NULL, 0,NULL, 'Surrey', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('748', 701, NULL, 0,NULL, 'Sussex', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('749', 701, NULL, 0,NULL, 'Tyne and Wear', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('750', 701, NULL, 0,NULL, 'Warwickshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('751', 701, NULL, 0,NULL, 'West Midlands', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('752', 701, NULL, 0,NULL, 'West Sussex', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('753', 701, NULL, 0,NULL, 'West Yorkshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('754', 701, NULL, 0,NULL, 'West Riding of Yorkshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('755', 701, NULL, 0,NULL, 'Wiltshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('756', 701, NULL, 0,NULL, 'Worcestershire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('757', 701, NULL, 0,NULL, 'Yorkshire', 1);
-- Wales
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('758', 702, NULL, 0,NULL, 'Anglesey', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('759', 702, NULL, 0,NULL, 'Breconshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('760', 702, NULL, 0,NULL, 'Caernarvonshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('761', 702, NULL, 0,NULL, 'Cardiganshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('762', 702, NULL, 0,NULL, 'Carmarthenshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('763', 702, NULL, 0,NULL, 'Ceredigion', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('764', 702, NULL, 0,NULL, 'Denbighshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('765', 702, NULL, 0,NULL, 'Flintshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('766', 702, NULL, 0,NULL, 'Glamorgan', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('767', 702, NULL, 0,NULL, 'Gwent', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('768', 702, NULL, 0,NULL, 'Gwynedd', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('769', 702, NULL, 0,NULL, 'Merionethshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('770', 702, NULL, 0,NULL, 'Monmouthshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('771', 702, NULL, 0,NULL, 'Mid Glamorgan', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('772', 702, NULL, 0,NULL, 'Montgomeryshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('773', 702, NULL, 0,NULL, 'Pembrokeshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('774', 702, NULL, 0,NULL, 'Powys', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('775', 702, NULL, 0,NULL, 'Radnorshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('776', 702, NULL, 0,NULL, 'South Glamorgan', 1);
-- Scotland
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('777', 703, NULL, 0,NULL, 'Aberdeen, City of', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('778', 703, NULL, 0,NULL, 'Angus', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('779', 703, NULL, 0,NULL, 'Argyll', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('780', 703, NULL, 0,NULL, 'Ayrshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('781', 703, NULL, 0,NULL, 'Banffshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('782', 703, NULL, 0,NULL, 'Berwickshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('783', 703, NULL, 0,NULL, 'Bute', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('784', 703, NULL, 0,NULL, 'Caithness', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('785', 703, NULL, 0,NULL, 'Clackmannanshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('786', 703, NULL, 0,NULL, 'Dumfriesshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('787', 703, NULL, 0,NULL, 'Dumbartonshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('788', 703, NULL, 0,NULL, 'Dundee, City of', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('789', 703, NULL, 0,NULL, 'East Lothian', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('790', 703, NULL, 0,NULL, 'Fife', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('791', 703, NULL, 0,NULL, 'Inverness', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('792', 703, NULL, 0,NULL, 'Kincardineshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('793', 703, NULL, 0,NULL, 'Kinross-shire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('794', 703, NULL, 0,NULL, 'Kirkcudbrightshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('795', 703, NULL, 0,NULL, 'Lanarkshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('796', 703, NULL, 0,NULL, 'Midlothian', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('797', 703, NULL, 0,NULL, 'Morayshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('798', 703, NULL, 0,NULL, 'Nairnshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('799', 703, NULL, 0,NULL, 'Orkney', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('800', 703, NULL, 0,NULL, 'Peebleshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('801', 703, NULL, 0,NULL, 'Perthshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('802', 703, NULL, 0,NULL, 'Renfrewshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('803', 703, NULL, 0,NULL, 'Ross & Cromarty', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('804', 703, NULL, 0,NULL, 'Roxburghshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('805', 703, NULL, 0,NULL, 'Selkirkshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('806', 703, NULL, 0,NULL, 'Shetland', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('807', 703, NULL, 0,NULL, 'Stirlingshire', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('808', 703, NULL, 0,NULL, 'Sutherland', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('809', 703, NULL, 0,NULL, 'West Lothian', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('810', 703, NULL, 0,NULL, 'Wigtownshire', 1);
-- Northern Ireland
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('811', 704, NULL, 0,NULL, 'Antrim', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('812', 704, NULL, 0,NULL, 'Armagh', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('813', 704, NULL, 0,NULL, 'Co. Down', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('814', 704, NULL, 0,NULL, 'Co. Fermanagh', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('815', 704, NULL, 0,NULL, 'Co. Londonderry', 1);



-- Provinces US (id country=11)
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('AL', 1101, '', 0, 'ALABAMA', 'Alabama', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('AK', 1101, '', 0, 'ALASKA', 'Alaska', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('AZ', 1101, '', 0, 'ARIZONA', 'Arizona', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('AR', 1101, '', 0, 'ARKANSAS', 'Arkansas', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('CA', 1101, '', 0, 'CALIFORNIA', 'California', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('CO', 1101, '', 0, 'COLORADO', 'Colorado', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('CT', 1101, '', 0, 'CONNECTICUT', 'Connecticut', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('DE', 1101, '', 0, 'DELAWARE', 'Delaware', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('FL', 1101, '', 0, 'FLORIDA', 'Florida', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('GA', 1101, '', 0, 'GEORGIA', 'Georgia', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('HI', 1101, '', 0, 'HAWAII', 'Hawaii', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('ID', 1101, '', 0, 'IDAHO', 'Idaho', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('IL', 1101, '', 0, 'ILLINOIS','Illinois', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('IN', 1101, '', 0, 'INDIANA', 'Indiana', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('IA', 1101, '', 0, 'IOWA', 'Iowa', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('KS', 1101, '', 0, 'KANSAS', 'Kansas', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('KY', 1101, '', 0, 'KENTUCKY', 'Kentucky', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('LA', 1101, '', 0, 'LOUISIANA', 'Louisiana', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('ME', 1101, '', 0, 'MAINE', 'Maine', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('MD', 1101, '', 0, 'MARYLAND', 'Maryland', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('MA', 1101, '', 0, 'MASSACHUSSETTS', 'Massachusetts', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('MI', 1101, '', 0, 'MICHIGAN', 'Michigan', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('MN', 1101, '', 0, 'MINNESOTA', 'Minnesota', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('MS', 1101, '', 0, 'MISSISSIPPI', 'Mississippi', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('MO', 1101, '', 0, 'MISSOURI', 'Missouri', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('MT', 1101, '', 0, 'MONTANA', 'Montana', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('NE', 1101, '', 0, 'NEBRASKA', 'Nebraska', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('NV', 1101, '', 0, 'NEVADA', 'Nevada', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('NH', 1101, '', 0, 'NEW HAMPSHIRE', 'New Hampshire', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('NJ', 1101, '', 0, 'NEW JERSEY', 'New Jersey', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('NM', 1101, '', 0, 'NEW MEXICO', 'New Mexico', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('NY', 1101, '', 0, 'NEW YORK', 'New York', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('NC', 1101, '', 0, 'NORTH CAROLINA', 'North Carolina', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('ND', 1101, '', 0, 'NORTH DAKOTA', 'North Dakota', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('OH', 1101, '', 0, 'OHIO', 'Ohio', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('OK', 1101, '', 0, 'OKLAHOMA', 'Oklahoma', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('OR', 1101, '', 0, 'OREGON', 'Oregon', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('PA', 1101, '', 0, 'PENNSYLVANIA', 'Pennsylvania', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('RI', 1101, '', 0, 'RHODE ISLAND', 'Rhode Island', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('SC', 1101, '', 0, 'SOUTH CAROLINA', 'South Carolina', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('SD', 1101, '', 0, 'SOUTH DAKOTA', 'South Dakota', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('TN', 1101, '', 0, 'TENNESSEE', 'Tennessee', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('TX', 1101, '', 0, 'TEXAS', 'Texas', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('UT', 1101, '', 0, 'UTAH', 'Utah', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('VT', 1101, '', 0, 'VERMONT', 'Vermont', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('VA', 1101, '', 0, 'VIRGINIA', 'Virginia', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('WA', 1101, '', 0, 'WASHINGTON', 'Washington', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('WV', 1101, '', 0, 'WEST VIRGINIA', 'West Virginia', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('WI', 1101, '', 0, 'WISCONSIN', 'Wisconsin', 1);
insert into llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) values ('WY', 1101, '', 0, 'WYOMING', 'Wyoming', 1);


-- San Salvador (id country=86)
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SS', 8601, '', 0, '', 'San Salvador', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SA', 8603, '', 0, '', 'Santa Ana', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('AH', 8603, '', 0, '', 'Ahuachapan', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SO', 8603, '', 0, '', 'Sonsonate', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('US', 8602, '', 0, '', 'Usulutan', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SM', 8602, '', 0, '', 'San Miguel', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('MO', 8602, '', 0, '', 'Morazan', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('LU', 8602, '', 0, '', 'La Union', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('LL', 8601, '', 0, '', 'La Libertad', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('CH', 8601, '', 0, '', 'Chalatenango', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('CA', 8601, '', 0, '', 'Cabañas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('LP', 8601, '', 0, '', 'La Paz', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SV', 8601, '', 0, '', 'San Vicente', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('CU', 8601, '', 0, '', 'Cuscatlan', 1);


-- Provinces India (id country=117)
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('AN', 11701, NULL, 0, 'AN', 'Andaman & Nicobar', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('AP', 11701, NULL, 0, 'AP', 'Andhra Pradesh', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('AR', 11701, NULL, 0, 'AR', 'Arunachal Pradesh', 1);     
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('AS', 11701, NULL, 0, 'AS', 'Assam', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('BR', 11701, NULL, 0, 'BR', 'Bihar', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('CG', 11701, NULL, 0, 'CG', 'Chattisgarh', 1);     
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('CH', 11701, NULL, 0, 'CH', 'Chandigarh', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('DD', 11701, NULL, 0, 'DD', 'Daman & Diu', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('DL', 11701, NULL, 0, 'DL', 'Delhi', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('DN', 11701, NULL, 0, 'DN', 'Dadra and Nagar Haveli', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('GA', 11701, NULL, 0, 'GA', 'Goa', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('GJ', 11701, NULL, 0, 'GJ', 'Gujarat', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('HP', 11701, NULL, 0, 'HP', 'Himachal Pradesh', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('HR', 11701, NULL, 0, 'HR', 'Haryana', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('JH', 11701, NULL, 0, 'JH', 'Jharkhand', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('JK', 11701, NULL, 0, 'JK', 'Jammu & Kashmir', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('KA', 11701, NULL, 0, 'KA', 'Karnataka', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('KL', 11701, NULL, 0, 'KL', 'Kerala', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('LD', 11701, NULL, 0, 'LD', 'Lakshadweep', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('MH', 11701, NULL, 0, 'MH', 'Maharashtra', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('ML', 11701, NULL, 0, 'ML', 'Meghalaya', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('MN', 11701, NULL, 0, 'MN', 'Manipur', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('MP', 11701, NULL, 0, 'MP', 'Madhya Pradesh', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('MZ', 11701, NULL, 0, 'MZ', 'Mizoram', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('NL', 11701, NULL, 0, 'NL', 'Nagaland', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('OR', 11701, NULL, 0, 'OR', 'Orissa', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('PB', 11701, NULL, 0, 'PB', 'Punjab', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('PY', 11701, NULL, 0, 'PY', 'Puducherry', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('RJ', 11701, NULL, 0, 'RJ', 'Rajasthan', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SK', 11701, NULL, 0, 'SK', 'Sikkim', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('TE', 11701, NULL, 0, 'TE', 'Telangana', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('TN', 11701, NULL, 0, 'TN', 'Tamil Nadu', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('TR', 11701, NULL, 0, 'TR', 'Tripura', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('UL', 11701, NULL, 0, 'UL', 'Uttarakhand', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('UP', 11701, NULL, 0, 'UP', 'Uttar Pradesh', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('WB', 11701, NULL, 0, 'WB', 'West Bengal', 1);

-- Provinces Indonesia (id country=118)
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('BA', 11801, NULL, 0, 'BA', 'Bali', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('BB', 11801, NULL, 0, 'BB', 'Bangka Belitung', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('BT', 11801, NULL, 0, 'BT', 'Banten', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('BE', 11801, NULL, 0, 'BA', 'Bengkulu', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('YO', 11801, NULL, 0, 'YO', 'DI Yogyakarta', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('JK', 11801, NULL, 0, 'JK', 'DKI Jakarta', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('GO', 11801, NULL, 0, 'GO', 'Gorontalo', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('JA', 11801, NULL, 0, 'JA', 'Jambi', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('JB', 11801, NULL, 0, 'JB', 'Jawa Barat', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('JT', 11801, NULL, 0, 'JT', 'Jawa Tengah', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('JI', 11801, NULL, 0, 'JI', 'Jawa Timur', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('KB', 11801, NULL, 0, 'KB', 'Kalimantan Barat', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('KS', 11801, NULL, 0, 'KS', 'Kalimantan Selatan', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('KT', 11801, NULL, 0, 'KT', 'Kalimantan Tengah', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('KI', 11801, NULL, 0, 'KI', 'Kalimantan Timur', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('KU', 11801, NULL, 0, 'KU', 'Kalimantan Utara', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('KR', 11801, NULL, 0, 'KR', 'Kepulauan Riau', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('LA', 11801, NULL, 0, 'LA', 'Lampung', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('MA', 11801, NULL, 0, 'MA', 'Maluku', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('MU', 11801, NULL, 0, 'MU', 'Maluku Utara', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('AC', 11801, NULL, 0, 'AC', 'Nanggroe Aceh Darussalam', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('NB', 11801, NULL, 0, 'NB', 'Nusa Tenggara Barat', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('NT', 11801, NULL, 0, 'NT', 'Nusa Tenggara Timur', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('PA', 11801, NULL, 0, 'PA', 'Papua', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('PB', 11801, NULL, 0, 'PB', 'Papua Barat', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('RI', 11801, NULL, 0, 'RI', 'Riau', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SR', 11801, NULL, 0, 'SR', 'Sulawesi Barat', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SN', 11801, NULL, 0, 'SN', 'Sulawesi Selatan', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('ST', 11801, NULL, 0, 'ST', 'Sulawesi Tengah', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SG', 11801, NULL, 0, 'SG', 'Sulawesi Tenggara', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SA', 11801, NULL, 0, 'SA', 'Sulawesi Utara', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SB', 11801, NULL, 0, 'SB', 'Sumatera Barat', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SS', 11801, NULL, 0, 'SS', 'Sumatera Selatan', 1);    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SU', 11801, NULL, 0, 'SU', 'Sumatera Utara	', 1);    

-- Provinces Mexique (id country=154)
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('CMX', 15401, '', 0, 'CMX', 'Ciudad de México', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('AGS', 15401, '', 0, 'AGS', 'Aguascalientes', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('BCN', 15401, '', 0, 'BCN', 'Baja California Norte', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('BCS', 15401, '', 0, 'BCS', 'Baja California Sur', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('CAM', 15401, '', 0, 'CAM', 'Campeche', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('CHP', 15401, '', 0, 'CHP', 'Chiapas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('CHI', 15401, '', 0, 'CHI', 'Chihuahua', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('COA', 15401, '', 0, 'COA', 'Coahuila', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('COL', 15401, '', 0, 'COL', 'Colima', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('DUR', 15401, '', 0, 'DUR', 'Durango', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('GTO', 15401, '', 0, 'GTO', 'Guanajuato', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('GRO', 15401, '', 0, 'GRO', 'Guerrero', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('HGO', 15401, '', 0, 'HGO', 'Hidalgo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('JAL', 15401, '', 0, 'JAL', 'Jalisco', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('MEX', 15401, '', 0, 'MEX', 'México', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('MIC', 15401, '', 0, 'MIC', 'Michoacán de Ocampo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('MOR', 15401, '', 0, 'MOR', 'Morelos', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('NAY', 15401, '', 0, 'NAY', 'Nayarit', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('NLE', 15401, '', 0, 'NLE', 'Nuevo León', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('OAX', 15401, '', 0, 'OAX', 'Oaxaca', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('PUE', 15401, '', 0, 'PUE', 'Puebla', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('QRO', 15401, '', 0, 'QRO', 'Querétaro', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('ROO', 15401, '', 0, 'ROO', 'Quintana Roo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SLP', 15401, '', 0, 'SLP', 'San Luis Potosí', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SIN', 15401, '', 0, 'SIN', 'Sinaloa', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SON', 15401, '', 0, 'SON', 'Sonora', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('TAB', 15401, '', 0, 'TAB', 'Tabasco', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('TAM', 15401, '', 0, 'TAM', 'Tamaulipas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('TLX', 15401, '', 0, 'TLX', 'Tlaxcala', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VER', 15401, '', 0, 'VER', 'Veracruz', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('YUC', 15401, '', 0, 'YUC', 'Yucatán', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('ZAC', 15401, '', 0, 'ZAC', 'Zacatecas', 1);


-- Provinces Venezuela (id country=232)
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-L', 23201, '', 0, 'VE-L', 'Mérida', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-T', 23201, '', 0, 'VE-T', 'Trujillo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-E', 23201, '', 0, 'VE-E', 'Barinas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-M', 23202, '', 0, 'VE-M', 'Miranda', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-W', 23202, '', 0, 'VE-W', 'Vargas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-A', 23202, '', 0, 'VE-A', 'Distrito Capital', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-D', 23203, '', 0, 'VE-D', 'Aragua', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-G', 23203, '', 0, 'VE-G', 'Carabobo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-I', 23204, '', 0, 'VE-I', 'Falcón', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-K', 23204, '', 0, 'VE-K', 'Lara', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-U', 23204, '', 0, 'VE-U', 'Yaracuy', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-F', 23205, '', 0, 'VE-F', 'Bolívar', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-X', 23205, '', 0, 'VE-X', 'Amazonas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-Y', 23205, '', 0, 'VE-Y', 'Delta Amacuro', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-O', 23206, '', 0, 'VE-O', 'Nueva Esparta', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-Z', 23206, '', 0, 'VE-Z', 'Dependencias Federales', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-C', 23207, '', 0, 'VE-C', 'Apure', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-J', 23207, '', 0, 'VE-J', 'Guárico', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-H', 23207, '', 0, 'VE-H', 'Cojedes', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-P', 23207, '', 0, 'VE-P', 'Portuguesa', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-B', 23208, '', 0, 'VE-B', 'Anzoátegui', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-N', 23208, '', 0, 'VE-N', 'Monagas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-R', 23208, '', 0, 'VE-R', 'Sucre', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-V', 23209, '', 0, 'VE-V', 'Zulia', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-S', 23209, '', 0, 'VE-S', 'Táchira', 1);


-- Provinces Peru (id country=181)
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0101', 18101, '', 0, '', 'Chachapoyas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0102', 18101, '', 0, '', 'Bagua', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0103', 18101, '', 0, '', 'Bongará', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0104', 18101, '', 0, '', 'Condorcanqui', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0105', 18101, '', 0, '', 'Luya', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0106', 18101, '', 0, '', 'Rodríguez de Mendoza', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0107', 18101, '', 0, '', 'Utcubamba', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0201', 18102, '', 0, '', 'Huaraz', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0202', 18102, '', 0, '', 'Aija', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0203', 18102, '', 0, '', 'Antonio Raymondi', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0204', 18102, '', 0, '', 'Asunción', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0205', 18102, '', 0, '', 'Bolognesi', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0206', 18102, '', 0, '', 'Carhuaz', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0207', 18102, '', 0, '', 'Carlos Fermín Fitzcarrald', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0208', 18102, '', 0, '', 'Casma', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0209', 18102, '', 0, '', 'Corongo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0210', 18102, '', 0, '', 'Huari', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0211', 18102, '', 0, '', 'Huarmey', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0212', 18102, '', 0, '', 'Huaylas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0213', 18102, '', 0, '', 'Mariscal Luzuriaga', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0214', 18102, '', 0, '', 'Ocros', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0215', 18102, '', 0, '', 'Pallasca', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0216', 18102, '', 0, '', 'Pomabamba', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0217', 18102, '', 0, '', 'Recuay', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0218', 18102, '', 0, '', 'Papá', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0219', 18102, '', 0, '', 'Sihuas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0220', 18102, '', 0, '', 'Yungay', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0301', 18103, '', 0, '', 'Abancay', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0302', 18103, '', 0, '', 'Andahuaylas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0303', 18103, '', 0, '', 'Antabamba', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0304', 18103, '', 0, '', 'Aymaraes', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0305', 18103, '', 0, '', 'Cotabambas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0306', 18103, '', 0, '', 'Chincheros', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0307', 18103, '', 0, '', 'Grau', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0401', 18104, '', 0, '', 'Arequipa', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0402', 18104, '', 0, '', 'Camaná', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0403', 18104, '', 0, '', 'Caravelí', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0404', 18104, '', 0, '', 'Castilla', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0405', 18104, '', 0, '', 'Caylloma', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0406', 18104, '', 0, '', 'Condesuyos', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0407', 18104, '', 0, '', 'Islay', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0408', 18104, '', 0, '', 'La Unión', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0501', 18105, '', 0, '', 'Huamanga', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0502', 18105, '', 0, '', 'Cangallo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0503', 18105, '', 0, '', 'Huanca Sancos', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0504', 18105, '', 0, '', 'Huanta', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0505', 18105, '', 0, '', 'La Mar', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0506', 18105, '', 0, '', 'Lucanas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0507', 18105, '', 0, '', 'Parinacochas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0508', 18105, '', 0, '', 'Páucar del Sara Sara', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0509', 18105, '', 0, '', 'Sucre', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0510', 18105, '', 0, '', 'Víctor Fajardo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0511', 18105, '', 0, '', 'Vilcas Huamán', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0601', 18106, '', 0, '', 'Cajamarca', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0602', 18106, '', 0, '', 'Cajabamba', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0603', 18106, '', 0, '', 'Celendín', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0604', 18106, '', 0, '', 'Chota', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0605', 18106, '', 0, '', 'Contumazá', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0606', 18106, '', 0, '', 'Cutervo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0607', 18106, '', 0, '', 'Hualgayoc', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0608', 18106, '', 0, '', 'Jaén', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0609', 18106, '', 0, '', 'San Ignacio', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0610', 18106, '', 0, '', 'San Marcos', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0611', 18106, '', 0, '', 'San Miguel', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0612', 18106, '', 0, '', 'San Pablo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0613', 18106, '', 0, '', 'Santa Cruz', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0701', 18107, '', 0, '', 'Callao', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0801', 18108, '', 0, '', 'Cusco', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0802', 18108, '', 0, '', 'Acomayo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0803', 18108, '', 0, '', 'Anta', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0804', 18108, '', 0, '', 'Calca', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0805', 18108, '', 0, '', 'Canas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0806', 18108, '', 0, '', 'Canchis', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0807', 18108, '', 0, '', 'Chumbivilcas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0808', 18108, '', 0, '', 'Espinar', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0809', 18108, '', 0, '', 'La Convención', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0810', 18108, '', 0, '', 'Paruro', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0811', 18108, '', 0, '', 'Paucartambo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0812', 18108, '', 0, '', 'Quispicanchi', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0813', 18108, '', 0, '', 'Urubamba', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0901', 18109, '', 0, '', 'Huancavelica', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0902', 18109, '', 0, '', 'Acobamba', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0903', 18109, '', 0, '', 'Angaraes', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0904', 18109, '', 0, '', 'Castrovirreyna', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0905', 18109, '', 0, '', 'Churcampa', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0906', 18109, '', 0, '', 'Huaytará', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('0907', 18109, '', 0, '', 'Tayacaja', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1001', 18110, '', 0, '', 'Huánuco', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1002', 18110, '', 0, '', 'Ambón', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1003', 18110, '', 0, '', 'Dos de Mayo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1004', 18110, '', 0, '', 'Huacaybamba', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1005', 18110, '', 0, '', 'Huamalíes', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1006', 18110, '', 0, '', 'Leoncio Prado', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1007', 18110, '', 0, '', 'Marañón', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1008', 18110, '', 0, '', 'Pachitea', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1009', 18110, '', 0, '', 'Puerto Inca', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1010', 18110, '', 0, '', 'Lauricocha', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1011', 18110, '', 0, '', 'Yarowilca', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1101', 18111, '', 0, '', 'Ica', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1102', 18111, '', 0, '', 'Chincha', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1103', 18111, '', 0, '', 'Nazca', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1104', 18111, '', 0, '', 'Palpa', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1105', 18111, '', 0, '', 'Pisco', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1201', 18112, '', 0, '', 'Huancayo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1202', 18112, '', 0, '', 'Concepción', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1203', 18112, '', 0, '', 'Chanchamayo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1204', 18112, '', 0, '', 'Jauja', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1205', 18112, '', 0, '', 'Junín', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1206', 18112, '', 0, '', 'Satipo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1207', 18112, '', 0, '', 'Tarma', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1208', 18112, '', 0, '', 'Yauli', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1209', 18112, '', 0, '', 'Chupaca', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1301', 18113, '', 0, '', 'Trujillo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1302', 18113, '', 0, '', 'Ascope', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1303', 18113, '', 0, '', 'Bolívar', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1304', 18113, '', 0, '', 'Chepén', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1305', 18113, '', 0, '', 'Julcán', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1306', 18113, '', 0, '', 'Otuzco', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1307', 18113, '', 0, '', 'Pacasmayo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1308', 18113, '', 0, '', 'Pataz', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1309', 18113, '', 0, '', 'Sánchez Carrión', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1310', 18113, '', 0, '', 'Santiago de Chuco', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1311', 18113, '', 0, '', 'Gran Chimú', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1312', 18113, '', 0, '', 'Virú', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1401', 18114, '', 0, '', 'Chiclayo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1402', 18114, '', 0, '', 'Ferreñafe', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1403', 18114, '', 0, '', 'Lambayeque', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1501', 18115, '', 0, '', 'Lima', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1502', 18116, '', 0, '', 'Huaura', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1503', 18116, '', 0, '', 'Barranca', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1504', 18116, '', 0, '', 'Cajatambo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1505', 18116, '', 0, '', 'Canta', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1506', 18116, '', 0, '', 'Cañete', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1507', 18116, '', 0, '', 'Huaral', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1508', 18116, '', 0, '', 'Huarochirí', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1509', 18116, '', 0, '', 'Oyón', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1510', 18116, '', 0, '', 'Yauyos', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1601', 18117, '', 0, '', 'Maynas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1602', 18117, '', 0, '', 'Alto Amazonas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1603', 18117, '', 0, '', 'Loreto', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1604', 18117, '', 0, '', 'Mariscal Ramón Castilla', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1605', 18117, '', 0, '', 'Requena', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1606', 18117, '', 0, '', 'Ucayali', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1607', 18117, '', 0, '', 'Datem del Marañón', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1701', 18118, '', 0, '', 'Tambopata', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1702', 18118, '', 0, '', 'Manú', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1703', 18118, '', 0, '', 'Tahuamanu', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1801', 18119, '', 0, '', 'Mariscal Nieto', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1802', 18119, '', 0, '', 'General Sánchez Cerro', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1803', 18119, '', 0, '', 'Ilo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1901', 18120, '', 0, '', 'Pasco', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1902', 18120, '', 0, '', 'Daniel Alcides Carrión', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('1903', 18120, '', 0, '', 'Oxapampa', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2001', 18121, '', 0, '', 'Piura', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2002', 18121, '', 0, '', 'Ayabaca', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2003', 18121, '', 0, '', 'Huancabamba', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2004', 18121, '', 0, '', 'Morropón', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2005', 18121, '', 0, '', 'Paita', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2006', 18121, '', 0, '', 'Sullana', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2007', 18121, '', 0, '', 'Talara', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2008', 18121, '', 0, '', 'Sechura', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2101', 18122, '', 0, '', 'Puno', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2102', 18122, '', 0, '', 'Azángaro', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2103', 18122, '', 0, '', 'Carabaya', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2104', 18122, '', 0, '', 'Chucuito', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2105', 18122, '', 0, '', 'El Collao', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2106', 18122, '', 0, '', 'Huancané', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2107', 18122, '', 0, '', 'Lampa', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2108', 18122, '', 0, '', 'Melgar', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2109', 18122, '', 0, '', 'Moho', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2110', 18122, '', 0, '', 'San Antonio de Putina', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2111', 18122, '', 0, '', 'San Román', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2112', 18122, '', 0, '', 'Sandia', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2113', 18122, '', 0, '', 'Yunguyo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2201', 18123, '', 0, '', 'Moyobamba', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2202', 18123, '', 0, '', 'Bellavista', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2203', 18123, '', 0, '', 'El Dorado', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2204', 18123, '', 0, '', 'Huallaga', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2205', 18123, '', 0, '', 'Lamas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2206', 18123, '', 0, '', 'Mariscal Cáceres', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2207', 18123, '', 0, '', 'Picota', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2208', 18123, '', 0, '', 'La Rioja', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2209', 18123, '', 0, '', 'San Martín', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2210', 18123, '', 0, '', 'Tocache', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2301', 18124, '', 0, '', 'Tacna', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2302', 18124, '', 0, '', 'Candarave', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2303', 18124, '', 0, '', 'Jorge Basadre', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2304', 18124, '', 0, '', 'Tarata', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2401', 18125, '', 0, '', 'Tumbes', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2402', 18125, '', 0, '', 'Contralmirante Villar', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2403', 18125, '', 0, '', 'Zarumilla', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2501', 18126, '', 0, '', 'Coronel Portillo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2502', 18126, '', 0, '', 'Atalaya', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2503', 18126, '', 0, '', 'Padre Abad', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('2504', 18126, '', 0, '', 'Purús', 1);

-- Provinces Panama (id country=178)
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('PA-1', 17801, '', 0, '', 'Bocas del Toro', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('PA-2', 17801, '', 0, '', 'Coclé', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('PA-3', 17801, '', 0, '', 'Colón', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('PA-4', 17801, '', 0, '', 'Chiriquí', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('PA-5', 17801, '', 0, '', 'Darién', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('PA-6', 17801, '', 0, '', 'Herrera', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('PA-7', 17801, '', 0, '', 'Los Santos', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('PA-8', 17801, '', 0, '', 'Panamá', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('PA-9', 17801, '', 0, '', 'Veraguas', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('PA-13', 17801, '', 0, '', 'Panamá Oeste', 1);

-- Provinces United Arab Emirates (id country=227)
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('AE-1', 22701, '', 0, '', 'Abu Dhabi', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('AE-2', 22701, '', 0, '', 'Dubai', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('AE-3', 22701, '', 0, '', 'Ajman', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('AE-4', 22701, '', 0, '', 'Fujairah', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('AE-5', 22701, '', 0, '', 'Ras al-Khaimah', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('AE-6', 22701, '', 0, '', 'Sharjah', 1);
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('AE-7', 22701, '', 0, '', 'Umm al-Quwain', 1);


-- Provinces (postal districts) Portugal (rowid country=25)
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('15001', 'PT-AV', NULL, NULL, 'AVEIRO', 'Aveiro');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('15002', 'PT-AC', NULL, NULL, 'AZORES', 'Azores');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('15001', 'PT-BE', NULL, NULL, 'BEJA', 'Beja');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('15001', 'PT-BR', NULL, NULL, 'BRAGA', 'Braga');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('15001', 'PT-BA', NULL, NULL, 'BRAGANCA', 'Bragança');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('15001', 'PT-CB', NULL, NULL, 'CASTELO BRANCO', 'Castelo Branco');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('15001', 'PT-CO', NULL, NULL, 'COIMBRA', 'Coimbra');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('15001', 'PT-EV', NULL, NULL, 'EVORA', 'Évora');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('15001', 'PT-FA', NULL, NULL, 'FARO', 'Faro');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('15001', 'PT-GU', NULL, NULL, 'GUARDA', 'Guarda');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('15001', 'PT-LE', NULL, NULL, 'LEIRIA', 'Leiria');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('15001', 'PT-LI', NULL, NULL, 'LISBON', 'Lisboa');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('15001', 'PT-AML',NULL, NULL, 'AREA METROPOLITANA LISBOA', 'Área Metropolitana de Lisboa');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('15002', 'PT-MA', NULL, NULL, 'MADEIRA', 'Madeira');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('15001', 'PT-PA', NULL, NULL, 'PORTALEGRE', 'Portalegre');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('15001', 'PT-PO', NULL, NULL, 'PORTO', 'Porto');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('15001', 'PT-SA', NULL, NULL, 'SANTAREM', 'Santarém');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('15001', 'PT-SE', NULL, NULL, 'SETUBAL', 'Setúbal');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('15001', 'PT-VC', NULL, NULL, 'VIANA DO CASTELO', 'Viana Do Castelo');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('15001', 'PT-VR', NULL, NULL, 'VILA REAL', 'Vila Real');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('15001', 'PT-VI', NULL, NULL, 'VISEU', 'Viseu');

-- Provinces Slovenia (rowid country=202)
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('20203', 'SI031', NULL, NULL, 'MURA', 'Mura');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('20203', 'SI032', NULL, NULL, 'DRAVA', 'Drava');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('20203', 'SI033', NULL, NULL, 'CARINTHIA', 'Carinthia');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('20203', 'SI034', NULL, NULL, 'SAVINJA', 'Savinja');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('20203', 'SI035', NULL, NULL, 'CENTRAL SAVA', 'Central Sava');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('20203', 'SI036', NULL, NULL, 'LOWER SAVA', 'Lower Sava');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('20203', 'SI037', NULL, NULL, 'SOUTHEAST SLOVENIA', 'Southeast Slovenia');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('20203', 'SI038', NULL, NULL, 'LITTORAL–INNER CARNIOLA', 'Littoral–Inner Carniola');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('20204', 'SI041', NULL, NULL, 'CENTRAL SLOVENIA', 'Central Slovenia');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('20204', 'SI038', NULL, NULL, 'UPPER CARNIOLA', 'Upper Carniola');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('20204', 'SI043', NULL, NULL, 'GORIZIA', 'Gorizia');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ('20204', 'SI044', NULL, NULL, 'COASTAL–KARST', 'Coastal–Karst');


-- Provinces  Taiwan (rowid country=886)
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-KLU', 'KLU', NULL, '基隆市');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-TPE', 'TPE', NULL, '臺北市');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-TPH', 'TPH', NULL, '新北市');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-TYC', 'TYC', NULL, '桃園市');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-HSH', 'HSH', NULL, '新竹縣');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-HSC', 'HSC', NULL, '新竹市');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-MAL', 'MAL', NULL, '苗栗縣');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-MAC', 'MAC', NULL, '苗栗市');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-TXG', 'TXG', NULL, '臺中市');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-CWH', 'CWH', NULL, '彰化縣');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-CWS', 'CWS', NULL, '彰化市');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-NTC', 'NTC', NULL, '南投市');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-NTO', 'NTO', NULL, '南投縣');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-YLH', 'YLH', NULL, '雲林縣');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-CHY', 'CHY', NULL, '嘉義縣');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-CYI', 'CYI', NULL, '嘉義市');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-TNN', 'TNN', NULL, '臺南市');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-KHH', 'KHH', NULL, '高雄市');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-IUH', 'IUH', NULL, '屏東縣');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-PTS', 'PTS', NULL, '屏東市');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-ILN', 'ILN', NULL, '宜蘭縣');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-ILC', 'ILC', NULL, '宜蘭市');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-HWA', 'HWA', NULL, '花蓮縣');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-HWC', 'HWC', NULL, '花蓮市');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-TTC', 'TTC', NULL, '臺東市');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-TTT', 'TTT', NULL, '臺東縣');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-PEH', 'PEH', NULL, '澎湖縣');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-GNI', 'GNI', NULL, '綠島');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-KYD', 'KYD', NULL, '蘭嶼');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-KMN', 'KMN', NULL, '金門縣');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, nom) VALUES (21301, 'TW-LNN', 'LNN', NULL, '連江縣');


