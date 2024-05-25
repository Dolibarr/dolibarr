-- file: /htdocs/install/mysql/data/llx_20_c_departements.sql

-- Authors -----------------------------------------------------------------------
-- Copyright (C) 2001-2004  Rodolphe Quiedeville   <rodolphe@quiedeville.org>
-- Copyright (C) 2003       Jean-Louis Bergamo     <jlb@j1b.org>
-- Copyright (C) 2004-2010  Laurent Destailleur    <eldy@users.sourceforge.net>
-- Copyright (C) 2004       Benoit Mortier         <benoit.mortier@opensides.be>
-- Copyright (C) 2004       Guillaume Delecourt    <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009  Regis Houssin          <regis.houssin@inodbox.com>
-- Copyright (C) 2007 	    Patrick Raguin         <patrick.raguin@gmail.com>
-- Copyright (C) 2010-2016  Juanjo Menent          <jmenent@2byte.es>
-- Copyright (C) 2012 	    Sebastian Neuwert      <sebastian.neuwert@modula71.de>
-- Copyright (C) 2012	    Ricardo Schluter       <info@ripasch.nl>
-- Copyright (C) 2015	    Ferran Marcet          <fmarcet@2byte.es>
-- Copyright (C) 2020-2023  Udo Tamm               <dev@dolibit.de>
-- Copyright (C) 2022       Miro Sertić            <miro.sertic0606@gmail.com>
-- Copyright (C) 2022       ButterflyOfFire        <butterflyoffire+dolibarr@protonmail.com>
--

-- License ----------------------------------------------------------------------
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
--
-- Table of Content (TOC) 
-- Departements/Cantons/Provinces/States:
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
-- Croatia
-- France
-- Germany
-- Greece
-- Honduras
-- Hungary
-- Italy
-- Japan
-- Luxembourg
-- Morocco
-- Netherlands
-- Panama
-- Peru
-- Portugal
-- Romania
-- San Salvador -> El Salvador 
-- Slovenia   (need to check code SI-Id)
-- Switzerland / Suisse
-- Taiwan
-- Tunisia
-- United States of America 
--
-- others .. unsorted  




-- TEMPLATE -------------------------------------------------------------------------------------------------------------
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ( 0, '0',  '0',0,'-','-');
--
-- field 'active' is not requiered - all lines are always set as active = on (1) by default


-- Algeria Provinces  (id country=13)
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '01', '', 0, '', 'Adrar');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '02', '', 0, '', 'Chlef');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '03', '', 0, '', 'Laghouat');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '04', '', 0, '', 'Oum El Bouaghi');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '05', '', 0, '', 'Batna');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '06', '', 0, '', 'Béjaïa');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '07', '', 0, '', 'Biskra');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '08', '', 0, '', 'Béchar');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '09', '', 0, '', 'Blida');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '10', '', 0, '', 'Bouira');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '11', '', 0, '', 'Tamanrasset');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '12', '', 0, '', 'Tébessa');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '13', '', 0, '', 'Tlemcen');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '14', '', 0, '', 'Tiaret');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '15', '', 0, '', 'Tizi Ouzou');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '16', '', 0, '', 'Alger');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '17', '', 0, '', 'Djelfa');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '18', '', 0, '', 'Jijel');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '19', '', 0, '', 'Sétif');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '20', '', 0, '', 'Saïda');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '21', '', 0, '', 'Skikda');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '22', '', 0, '', 'Sidi Bel Abbès');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '23', '', 0, '', 'Annaba');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '24', '', 0, '', 'Guelma');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '25', '', 0, '', 'Constantine');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '26', '', 0, '', 'Médéa');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '27', '', 0, '', 'Mostaganem');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '28', '', 0, '', 'M''Sila');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '29', '', 0, '', 'Mascara');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '30', '', 0, '', 'Ouargla');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '31', '', 0, '', 'Oran');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '32', '', 0, '', 'El Bayadh');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '33', '', 0, '', 'Illizi');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '34', '', 0, '', 'Bordj Bou Arreridj');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '35', '', 0, '', 'Boumerdès');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '36', '', 0, '', 'El Tarf');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '37', '', 0, '', 'Tindouf');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '38', '', 0, '', 'Tissemsilt');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '39', '', 0, '', 'El Oued');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '40', '', 0, '', 'Khenchela');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '41', '', 0, '', 'Souk Ahras');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '42', '', 0, '', 'Tipaza');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '43', '', 0, '', 'Mila');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '44', '', 0, '', 'Aïn Defla');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '45', '', 0, '', 'Naâma');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '46', '', 0, '', 'Aïn Témouchent');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '47', '', 0, '', 'Ghardaïa');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '48', '', 0, '', 'Relizane');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '49', '', 0, '', 'Timimoun');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '50', '', 0, '', 'Bordj Badji Mokhtar');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '51', '', 0, '', 'Ouled Djellal');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '52', '', 0, '', 'Béni Abbès');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '53', '', 0, '', 'In Salah');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '54', '', 0, '', 'In Guezzam');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '55', '', 0, '', 'Touggourt');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '56', '', 0, '', 'Djanet');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '57', '', 0, '', 'El M''Ghair');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1301, '58', '', 0, '', 'El Ménéa');


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
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (35001, 'AO-LSU', NULL, NULL, 'LUNDA-SUL', 'Lunda-Sul');
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
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2801, 'NSW', '', 1, '', 'New South Wales');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2801, 'VIC', '', 1, '', 'Victoria');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2801, 'QLD', '', 1, '', 'Queensland');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2801, 'SA' , '', 1, '', 'South Australia');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2801, 'ACT', '', 1, '', 'Australia Capital Territory');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2801, 'TAS', '', 1, '', 'Tasmania');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2801, 'WA' , '', 1, '', 'Western Australia');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (2801, 'NT' , '', 1, '', 'Northern Territory');


-- Austria States / Österreich Bundesländer (id country=41)
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (4101, 'B',  'BURGENLAND', 'Burgenland');
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (4101, 'K',  'KAERNTEN', 'Kärnten');
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (4101, 'N',  'NIEDEROESTERREICH', 'Niederösterreich');
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (4101, 'O',  'OBEROESTERREICH', 'Oberösterreich');
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (4101, 'S',  'SALZBURG', 'Salzburg');
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (4101, 'ST', 'STEIERMARK', 'Steiermark');
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (4101, 'T',  'TIROL', 'Tirol');
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (4101, 'V',  'VORARLBERG', 'Vorarlberg');
INSERT INTO llx_c_departements (fk_region, code_departement, ncc, nom) VALUES (4101, 'W',  'WIEN', 'Wien');


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
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (201, '01', '', 1, 'ANVERS', 'Anvers');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (203, '02', '', 3, 'BRUXELLES-CAPITALE', 'Bruxelles-Capitale');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (202, '03', '', 2, 'BRABANT-WALLON', 'Brabant-Wallon');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (201, '04', '', 1, 'BRABANT-FLAMAND', 'Brabant-Flamand');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (201, '05', '', 1, 'FLANDRE-OCCIDENTALE', 'Flandre-Occidentale');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (201, '06', '', 1, 'FLANDRE-ORIENTALE', 'Flandre-Orientale');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (202, '07', '', 2, 'HAINAUT', 'Hainaut');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (202, '08', '', 2, 'LIEGE', 'Liège');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (202, '09', '', 1, 'LIMBOURG', 'Limbourg');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (202, '10', '', 2, 'LUXEMBOURG', 'Luxembourg');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (202, '11', '', 2, 'NAMUR', 'Namur');


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
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1401, 'ON', '', 1, '', 'Ontario');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1401, 'QC', '', 1, '', 'Quebec');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1401, 'NS', '', 1, '', 'Nova Scotia');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1401, 'NB', '', 1, '', 'New Brunswick');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1401, 'MB', '', 1, '', 'Manitoba');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1401, 'BC', '', 1, '', 'British Columbia');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1401, 'PE', '', 1, '', 'Prince Edward Island');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1401, 'SK', '', 1, '', 'Saskatchewan');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1401, 'AB', '', 1, '', 'Alberta');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1401, 'NL', '', 1, '', 'Newfoundland and Labrador');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1401, 'YT', '', 1, '', 'Yukon');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1401, 'NT', '', 1, '', 'Northwest Territories');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1401, 'NU', '', 1, '', 'Nunavut');


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


-- Croatia Departments (id country=76)
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7601, 'HR-01', 'Bjelovar', 0, NULL, 'Bjelovarsko-bilogorska županija');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7601, 'HR-02', 'Karlovac', 0, NULL, 'Karlovačka županija');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7601, 'HR-03', 'Koprivnica', 0, NULL, 'Koprivničko-križevačka županija');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7601, 'HR-04', 'Krapina', 0, NULL, 'Krapinsko-zagorska županija');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7601, 'HR-05', 'Gospić', 0, NULL, 'Ličko-senjska županija');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7601, 'HR-06', 'Čakovec', 0, NULL, 'Međimurska županija');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7601, 'HR-07', 'Rijeka', 0, NULL, 'Primorsko-goranska županija');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7601, 'HR-08', 'Sisak', 0, NULL, 'Sisačko-moslavačka županija');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7601, 'HR-09', 'Varaždin', 0, NULL, 'Varaždinska županija');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7601, 'HR-10', 'Zagreb', 0, NULL, 'Zagrebačka županija');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7601, 'HR-11', 'Zagreb', 0, NULL, 'Grad Zagreb');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7602, 'HR-12', 'Zadar', 0, NULL, 'Zadarska županija');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7602, 'HR-13', 'Šibenik', 0, NULL, 'Šibensko-kninska županija');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7602, 'HR-14', 'Split', 0, NULL, 'Splitsko-dalmatinska županija');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7602, 'HR-15', 'Dubrovnik', 0, NULL, 'Dubrovačko-neretvanska županija');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7603, 'HR-16', 'Slavonski Brod', 0, NULL, 'Brodsko-posavska županija');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7603, 'HR-17', 'Osijek', 0, NULL, 'Osječko-baranjska županija');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7603, 'HR-18', 'Požega', 0, NULL, 'Požeško-slavonska županija');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7603, 'HR-19', 'Virovitica', 0, NULL, 'Virovitičko-podravska županija');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7603, 'HR-20', 'Vukovar', 0, NULL, 'Vukovarsko-srijemska županija');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (7604, 'HR-21', 'Pazin', 0, NULL, 'Istarska županija');


-- France Departements (id country=1)
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values ( 1, '971','97105',3, 'GUADELOUPE','Guadeloupe');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values ( 2, '972','97209',3, 'MARTINIQUE','Martinique');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values ( 3, '973','97302',3, 'GUYANE','Guyane');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values ( 4, '974','97411',3, 'REUNION','Réunion');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values ( 6, '976','97601',3, 'MAYOTTE','Mayotte');

insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (84, '01', '01053',5, 'AIN', 'Ain');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (32, '02', '02408',5, 'AISNE', 'Aisne');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (84, '03', '03190',5, 'ALLIER', 'Allier');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (93, '04', '04070',4, 'ALPES-DE-HAUTE-PROVENCE', 'Alpes-de-Haute-Provence');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (93, '05', '05061',4, 'HAUTES-ALPES', 'Hautes-Alpes');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (93, '06', '06088',4, 'ALPES-MARITIMES', 'Alpes-Maritimes');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (84, '07', '07186',5, 'ARDECHE', 'Ardèche');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (44, '08', '08105',4, 'ARDENNES', 'Ardennes');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (76, '09', '09122',5, 'ARIEGE', 'Ariège');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (44, '10', '10387',5, 'AUBE', 'Aube');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (76, '11', '11069',5, 'AUDE', 'Aude');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (76, '12', '12202',5, 'AVEYRON', 'Aveyron');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (93, '13', '13055',4, 'BOUCHES-DU-RHONE', 'Bouches-du-Rhône');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (28, '14', '14118',2, 'CALVADOS', 'Calvados');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (84, '15', '15014',2, 'CANTAL', 'Cantal');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (75, '16', '16015',3, 'CHARENTE', 'Charente');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (75, '17', '17300',3, 'CHARENTE-MARITIME', 'Charente-Maritime');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (24, '18', '18033',2, 'CHER', 'Cher');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (75, '19', '19272',3, 'CORREZE', 'Corrèze');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (94, '2A', '2A004',3, 'CORSE-DU-SUD', 'Corse-du-Sud');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (94, '2B', '2B033',3, 'HAUTE-CORSE', 'Haute-Corse');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (27, '21', '21231',3, 'COTE-D OR', 'Côte-d Or');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (53, '22', '22278',4, 'COTES-D ARMOR', 'Côtes-d Armor');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (75, '23', '23096',3, 'CREUSE', 'Creuse');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (75, '24', '24322',3, 'DORDOGNE', 'Dordogne');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (27, '25', '25056',2, 'DOUBS', 'Doubs');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (84, '26', '26362',3, 'DROME', 'Drôme');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (28, '27', '27229',5, 'EURE', 'Eure');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (24, '28', '28085',1, 'EURE-ET-LOIR', 'Eure-et-Loir');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (53, '29', '29232',2, 'FINISTERE', 'Finistère');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (76, '30', '30189',2, 'GARD', 'Gard');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (76, '31', '31555',3, 'HAUTE-GARONNE', 'Haute-Garonne');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (76, '32', '32013',2, 'GERS', 'Gers');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (75, '33', '33063',3, 'GIRONDE', 'Gironde');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (76, '34', '34172',5, 'HERAULT', 'Hérault');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (53, '35', '35238',1, 'ILLE-ET-VILAINE', 'Ille-et-Vilaine');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (24, '36', '36044',5, 'INDRE', 'Indre');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (24, '37', '37261',1, 'INDRE-ET-LOIRE', 'Indre-et-Loire');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (84, '38', '38185',5, 'ISERE', 'Isère');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (27, '39', '39300',2, 'JURA', 'Jura');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (75, '40', '40192',4, 'LANDES', 'Landes');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (24, '41', '41018',0, 'LOIR-ET-CHER', 'Loir-et-Cher');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (84, '42', '42218',3, 'LOIRE', 'Loire');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (84, '43', '43157',3, 'HAUTE-LOIRE', 'Haute-Loire');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (52, '44', '44109',3, 'LOIRE-ATLANTIQUE', 'Loire-Atlantique');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (24, '45', '45234',2, 'LOIRET', 'Loiret');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (76, '46', '46042',2, 'LOT', 'Lot');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (75, '47', '47001',0, 'LOT-ET-GARONNE', 'Lot-et-Garonne');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (76, '48', '48095',3, 'LOZERE', 'Lozère');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (52, '49', '49007',0, 'MAINE-ET-LOIRE', 'Maine-et-Loire');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (28, '50', '50502',3, 'MANCHE', 'Manche');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (44, '51', '51108',3, 'MARNE', 'Marne');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (44, '52', '52121',3, 'HAUTE-MARNE', 'Haute-Marne');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (52, '53', '53130',3, 'MAYENNE', 'Mayenne');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (44, '54', '54395',0, 'MEURTHE-ET-MOSELLE', 'Meurthe-et-Moselle');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (44, '55', '55029',3, 'MEUSE', 'Meuse');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (53, '56', '56260',2, 'MORBIHAN', 'Morbihan');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (44, '57', '57463',3, 'MOSELLE', 'Moselle');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (27, '58', '58194',3, 'NIEVRE', 'Nièvre');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (32, '59', '59350',2, 'NORD', 'Nord');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (32, '60', '60057',5, 'OISE', 'Oise');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (28, '61', '61001',5, 'ORNE', 'Orne');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (32, '62', '62041',2, 'PAS-DE-CALAIS', 'Pas-de-Calais');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (84, '63', '63113',2, 'PUY-DE-DOME', 'Puy-de-Dôme');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (75, '64', '64445',4, 'PYRENEES-ATLANTIQUES', 'Pyrénées-Atlantiques');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (76, '65', '65440',4, 'HAUTES-PYRENEES', 'Hautes-Pyrénées');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (76, '66', '66136',4, 'PYRENEES-ORIENTALES', 'Pyrénées-Orientales');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (44, '67', '67482',2, 'BAS-RHIN', 'Bas-Rhin');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (44, '68', '68066',2, 'HAUT-RHIN', 'Haut-Rhin');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (84, '69', '69123',2, 'RHONE', 'Rhône');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (27, '70', '70550',3, 'HAUTE-SAONE', 'Haute-Saône');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (27, '71', '71270',0, 'SAONE-ET-LOIRE', 'Saône-et-Loire');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (52, '72', '72181',3, 'SARTHE', 'Sarthe');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (84, '73', '73065',3, 'SAVOIE', 'Savoie');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (84, '74', '74010',3, 'HAUTE-SAVOIE', 'Haute-Savoie');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (11, '75', '75056',0, 'PARIS', 'Paris');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (28, '76', '76540',3, 'SEINE-MARITIME', 'Seine-Maritime');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (11, '77', '77288',0, 'SEINE-ET-MARNE', 'Seine-et-Marne');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (11, '78', '78646',4, 'YVELINES', 'Yvelines');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (75, '79', '79191',4, 'DEUX-SEVRES', 'Deux-Sèvres');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (32, '80', '80021',3, 'SOMME', 'Somme');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (76, '81', '81004',2, 'TARN', 'Tarn');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (76, '82', '82121',0, 'TARN-ET-GARONNE', 'Tarn-et-Garonne');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (93, '83', '83137',2, 'VAR', 'Var');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (93, '84', '84007',0, 'VAUCLUSE', 'Vaucluse');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (52, '85', '85191',3, 'VENDEE', 'Vendée');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (75, '86', '86194',3, 'VIENNE', 'Vienne');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (75, '87', '87085',3, 'HAUTE-VIENNE', 'Haute-Vienne');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (44, '88', '88160',4, 'VOSGES', 'Vosges');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (27, '89', '89024',5, 'YONNE', 'Yonne');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (27, '90', '90010',0, 'TERRITOIRE DE BELFORT', 'Territoire de Belfort');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (11, '91', '91228',5, 'ESSONNE', 'Essonne');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (11, '92', '92050',4, 'HAUTS-DE-SEINE', 'Hauts-de-Seine');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (11, '93', '93008',3, 'SEINE-SAINT-DENIS', 'Seine-Saint-Denis');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (11, '94', '94028',2, 'VAL-DE-MARNE', 'Val-de-Marne');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (11, '95', '95500',2, 'VAL-D OISE', 'Val-d Oise');


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


-- Greece Provinces (id country=102)
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('66', 10201, '', 0, '', 'Αθήνα');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('67', 10205, '', 0, '', 'Δράμα');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('01', 10205, '', 0, '', 'Έβρος');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('02', 10205, '', 0, '', 'Θάσος');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('03', 10205, '', 0, '', 'Καβάλα');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('04', 10205, '', 0, '', 'Ξάνθη');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('05', 10205, '', 0, '', 'Ροδόπη');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('06', 10203, '', 0, '', 'Ημαθία');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('07', 10203, '', 0, '', 'Θεσσαλονίκη');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('08', 10203, '', 0, '', 'Κιλκίς');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('09', 10203, '', 0, '', 'Πέλλα');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('10', 10203, '', 0, '', 'Πιερία');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('11', 10203, '', 0, '', 'Σέρρες');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('12', 10203, '', 0, '', 'Χαλκιδική');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('13', 10206, '', 0, '', 'Άρτα');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('14', 10206, '', 0, '', 'Θεσπρωτία');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('15', 10206, '', 0, '', 'Ιωάννινα');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('16', 10206, '', 0, '', 'Πρέβεζα');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('17', 10213, '', 0, '', 'Γρεβενά');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('18', 10213, '', 0, '', 'Καστοριά');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('19', 10213, '', 0, '', 'Κοζάνη');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('20', 10213, '', 0, '', 'Φλώρινα');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('21', 10212, '', 0, '', 'Καρδίτσα');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('22', 10212, '', 0, '', 'Λάρισα');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('23', 10212, '', 0, '', 'Μαγνησία');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('24', 10212, '', 0, '', 'Τρίκαλα');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('25', 10212, '', 0, '', 'Σποράδες');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('26', 10212, '', 0, '', 'Βοιωτία');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('27', 10202, '', 0, '', 'Εύβοια');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('28', 10202, '', 0, '', 'Ευρυτανία');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('29', 10202, '', 0, '', 'Φθιώτιδα');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('30', 10202, '', 0, '', 'Φωκίδα');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('31', 10209, '', 0, '', 'Αργολίδα');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('32', 10209, '', 0, '', 'Αρκαδία');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('33', 10209, '', 0, '', 'Κορινθία');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('34', 10209, '', 0, '', 'Λακωνία');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('35', 10209, '', 0, '', 'Μεσσηνία');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('36', 10211, '', 0, '', 'Αιτωλοακαρνανία');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('37', 10211, '', 0, '', 'Αχαΐα');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('38', 10211, '', 0, '', 'Ηλεία');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('39', 10207, '', 0, '', 'Ζάκυνθος');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('40', 10207, '', 0, '', 'Κέρκυρα');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('41', 10207, '', 0, '', 'Κεφαλληνία');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('42', 10207, '', 0, '', 'Ιθάκη');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('43', 10207, '', 0, '', 'Λευκάδα');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('44', 10208, '', 0, '', 'Ικαρία');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('45', 10208, '', 0, '', 'Λέσβος');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('46', 10208, '', 0, '', 'Λήμνος');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('47', 10208, '', 0, '', 'Σάμος');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('48', 10208, '', 0, '', 'Χίος');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('49', 10210, '', 0, '', 'Άνδρος');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('50', 10210, '', 0, '', 'Θήρα');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('51', 10210, '', 0, '', 'Κάλυμνος');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('52', 10210, '', 0, '', 'Κάρπαθος');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('53', 10210, '', 0, '', 'Κέα-Κύθνος');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('54', 10210, '', 0, '', 'Κω');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('55', 10210, '', 0, '', 'Μήλος');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('56', 10210, '', 0, '', 'Μύκονος');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('57', 10210, '', 0, '', 'Νάξος');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('58', 10210, '', 0, '', 'Πάρος');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('59', 10210, '', 0, '', 'Ρόδος');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('60', 10210, '', 0, '', 'Σύρος');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('61', 10210, '', 0, '', 'Τήνος');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('62', 10204, '', 0, '', 'Ηράκλειο');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('63', 10204, '', 0, '', 'Λασίθι');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('64', 10204, '', 0, '', 'Ρέθυμνο');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('65', 10204, '', 0, '', 'Χανιά');


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


-- Italy Provinces (id=3)
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (315, 'AG', NULL, NULL, NULL, 'AGRIGENTO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (312, 'AL', NULL, NULL, NULL, 'ALESSANDRIA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (310, 'AN', NULL, NULL, NULL, 'ANCONA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (319, 'AO', NULL, NULL, NULL, 'AOSTA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (316, 'AR', NULL, NULL, NULL, 'AREZZO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (310, 'AP', NULL, NULL, NULL, 'ASCOLI PICENO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (312, 'AT', NULL, NULL, NULL, 'ASTI');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (304, 'AV', NULL, NULL, NULL, 'AVELLINO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (313, 'BA', NULL, NULL, NULL, 'BARI');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (313, 'BT', NULL, NULL, NULL, 'BARLETTA-ANDRIA-TRANI');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (320, 'BL', NULL, NULL, NULL, 'BELLUNO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (304, 'BN', NULL, NULL, NULL, 'BENEVENTO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (309, 'BG', NULL, NULL, NULL, 'BERGAMO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (312, 'BI', NULL, NULL, NULL, 'BIELLA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (305, 'BO', NULL, NULL, NULL, 'BOLOGNA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (317, 'BZ', NULL, NULL, NULL, 'BOLZANO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (309, 'BS', NULL, NULL, NULL, 'BRESCIA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (313, 'BR', NULL, NULL, NULL, 'BRINDISI');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (314, 'CA', NULL, NULL, NULL, 'CAGLIARI');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (315, 'CL', NULL, NULL, NULL, 'CALTANISSETTA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (311, 'CB', NULL, NULL, NULL, 'CAMPOBASSO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (314, 'CI', NULL, NULL, NULL, 'CARBONIA-IGLESIAS');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (304, 'CE', NULL, NULL, NULL, 'CASERTA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (315, 'CT', NULL, NULL, NULL, 'CATANIA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (303, 'CZ', NULL, NULL, NULL, 'CATANZARO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (301, 'CH', NULL, NULL, NULL, 'CHIETI');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (309, 'CO', NULL, NULL, NULL, 'COMO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (303, 'CS', NULL, NULL, NULL, 'COSENZA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (309, 'CR', NULL, NULL, NULL, 'CREMONA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (303, 'KR', NULL, NULL, NULL, 'CROTONE');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (312, 'CN', NULL, NULL, NULL, 'CUNEO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (315, 'EN', NULL, NULL, NULL, 'ENNA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (310, 'FM', NULL, NULL, NULL, 'FERMO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (305, 'FE', NULL, NULL, NULL, 'FERRARA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (316, 'FI', NULL, NULL, NULL, 'FIRENZE');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (313, 'FG', NULL, NULL, NULL, 'FOGGIA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (305, 'FC', NULL, NULL, NULL, 'FORLI-CESENA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (307, 'FR', NULL, NULL, NULL, 'FROSINONE');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (308, 'GE', NULL, NULL, NULL, 'GENOVA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (306, 'GO', NULL, NULL, NULL, 'GORIZIA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (316, 'GR', NULL, NULL, NULL, 'GROSSETO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (308, 'IM', NULL, NULL, NULL, 'IMPERIA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (311, 'IS', NULL, NULL, NULL, 'ISERNIA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (308, 'SP', NULL, NULL, NULL, 'LA SPEZIA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (301, 'AQ', NULL, NULL, NULL, 'L AQUILA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (307, 'LT', NULL, NULL, NULL, 'LATINA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (313, 'LE', NULL, NULL, NULL, 'LECCE');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (309, 'LC', NULL, NULL, NULL, 'LECCO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (316, 'LI', NULL, NULL, NULL, 'LIVORNO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (309, 'LO', NULL, NULL, NULL, 'LODI');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (316, 'LU', NULL, NULL, NULL, 'LUCCA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (310, 'MC', NULL, NULL, NULL, 'MACERATA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (309, 'MN', NULL, NULL, NULL, 'MANTOVA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (316, 'MS', NULL, NULL, NULL, 'MASSA-CARRARA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (302, 'MT', NULL, NULL, NULL, 'MATERA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (314, 'VS', NULL, NULL, NULL, 'MEDIO CAMPIDANO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (315, 'ME', NULL, NULL, NULL, 'MESSINA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (309, 'MI', NULL, NULL, NULL, 'MILANO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (309, 'MB', NULL, NULL, NULL, 'MONZA e BRIANZA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (305, 'MO', NULL, NULL, NULL, 'MODENA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (304, 'NA', NULL, NULL, NULL, 'NAPOLI');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (312, 'NO', NULL, NULL, NULL, 'NOVARA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (314, 'NU', NULL, NULL, NULL, 'NUORO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (314, 'OG', NULL, NULL, NULL, 'OGLIASTRA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (314, 'OT', NULL, NULL, NULL, 'OLBIA-TEMPIO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (314, 'OR', NULL, NULL, NULL, 'ORISTANO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (320, 'PD', NULL, NULL, NULL, 'PADOVA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (315, 'PA', NULL, NULL, NULL, 'PALERMO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (305, 'PR', NULL, NULL, NULL, 'PARMA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (309, 'PV', NULL, NULL, NULL, 'PAVIA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (318, 'PG', NULL, NULL, NULL, 'PERUGIA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (310, 'PU', NULL, NULL, NULL, 'PESARO e URBINO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (301, 'PE', NULL, NULL, NULL, 'PESCARA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (305, 'PC', NULL, NULL, NULL, 'PIACENZA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (316, 'PI', NULL, NULL, NULL, 'PISA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (316, 'PT', NULL, NULL, NULL, 'PISTOIA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (306, 'PN', NULL, NULL, NULL, 'PORDENONE');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (302, 'PZ', NULL, NULL, NULL, 'POTENZA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (316, 'PO', NULL, NULL, NULL, 'PRATO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (315, 'RG', NULL, NULL, NULL, 'RAGUSA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (305, 'RA', NULL, NULL, NULL, 'RAVENNA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (303, 'RC', NULL, NULL, NULL, 'REGGIO CALABRIA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (305, 'RE', NULL, NULL, NULL, 'REGGIO NELL EMILIA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (307, 'RI', NULL, NULL, NULL, 'RIETI');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (305, 'RN', NULL, NULL, NULL, 'RIMINI');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (307, 'RM', NULL, NULL, NULL, 'ROMA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (320, 'RO', NULL, NULL, NULL, 'ROVIGO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (304, 'SA', NULL, NULL, NULL, 'SALERNO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (314, 'SS', NULL, NULL, NULL, 'SASSARI');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (308, 'SV', NULL, NULL, NULL, 'SAVONA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (316, 'SI', NULL, NULL, NULL, 'SIENA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (315, 'SR', NULL, NULL, NULL, 'SIRACUSA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (309, 'SO', NULL, NULL, NULL, 'SONDRIO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (313, 'TA', NULL, NULL, NULL, 'TARANTO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (301, 'TE', NULL, NULL, NULL, 'TERAMO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (318, 'TR', NULL, NULL, NULL, 'TERNI');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (312, 'TO', NULL, NULL, NULL, 'TORINO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (315, 'TP', NULL, NULL, NULL, 'TRAPANI');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (317, 'TN', NULL, NULL, NULL, 'TRENTO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (320, 'TV', NULL, NULL, NULL, 'TREVISO');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (306, 'TS', NULL, NULL, NULL, 'TRIESTE');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (306, 'UD', NULL, NULL, NULL, 'UDINE');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (309, 'VA', NULL, NULL, NULL, 'VARESE');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (320, 'VE', NULL, NULL, NULL, 'VENEZIA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (312, 'VB', NULL, NULL, NULL, 'VERBANO-CUSIO-OSSOLA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (312, 'VC', NULL, NULL, NULL, 'VERCELLI');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (320, 'VR', NULL, NULL, NULL, 'VERONA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (303, 'VV', NULL, NULL, NULL, 'VIBO VALENTIA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (320, 'VI', NULL, NULL, NULL, 'VICENZA');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (307, 'VT', NULL, NULL, NULL, 'VITERBO');


-- Japan 都道府県 (id country=123)
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '01', '', 0, '北海', '北海道', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '02', '', 0, '青森', '青森県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '03', '', 0, '岩手', '岩手県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '04', '', 0, '宮城', '宮城県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '05', '', 0, '秋田', '秋田県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '06', '', 0, '山形', '山形県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '07', '', 0, '福島', '福島県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '08', '', 0, '茨城', '茨城県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '09', '', 0, '栃木', '栃木県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '10', '', 0, '群馬', '群馬県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '11', '', 0, '埼玉', '埼玉県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '12', '', 0, '千葉', '千葉県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '13', '', 0, '東京', '東京都', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '14', '', 0, '神奈川', '神奈川県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '15', '', 0, '新潟', '新潟県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '16', '', 0, '富山', '富山県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '17', '', 0, '石川', '石川県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '18', '', 0, '福井', '福井県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '19', '', 0, '山梨', '山梨県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '20', '', 0, '長野', '長野県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '21', '', 0, '岐阜', '岐阜県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '22', '', 0, '静岡', '静岡県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '23', '', 0, '愛知', '愛知県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '24', '', 0, '三重', '三重県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '25', '', 0, '滋賀', '滋賀県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '26', '', 0, '京都', '京都府', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '27', '', 0, '大阪', '大阪府', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '28', '', 0, '兵庫', '兵庫県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '29', '', 0, '奈良', '奈良県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '30', '', 0, '和歌山', '和歌山県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '31', '', 0, '鳥取', '鳥取県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '32', '', 0, '島根', '島根県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '33', '', 0, '岡山', '岡山県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '34', '', 0, '広島', '広島県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '35', '', 0, '山口', '山口県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '36', '', 0, '徳島', '徳島県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '37', '', 0, '香川', '香川県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '38', '', 0, '愛媛', '愛媛県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '39', '', 0, '高知', '高知県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '40', '', 0, '福岡', '福岡県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '41', '', 0, '佐賀', '佐賀県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '42', '', 0, '長崎', '長崎県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '43', '', 0, '熊本', '熊本県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '44', '', 0, '大分', '大分県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '45', '', 0, '宮崎', '宮崎県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '46', '', 0, '鹿児島', '鹿児島県', 1);
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom, active) values (12301, '47', '', 0, '沖縄', '沖縄県', 1);


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


-- Morocco/Maroc Provinces (62) & Prefectures (13)  (id country=12)
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1209, 'MA',   '', 0, '', 'Province de Benslimane');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1209, 'MA1',  '', 0, '', 'Province de Berrechid');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1209, 'MA2',  '', 0, '', 'Province de Khouribga');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1209, 'MA3',  '', 0, '', 'Province de Settat');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1210, 'MA4',  '', 0, '', 'Province d''El Jadida');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1210, 'MA5',  '', 0, '', 'Province de Safi');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1210, 'MA6',  '', 0, '', 'Province de Sidi Bennour');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1210, 'MA7',  '', 0, '', 'Province de Youssoufia');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1205, 'MA6B', '', 0, '', 'Préfecture de Fès');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1205, 'MA7B', '', 0, '', 'Province de Boulemane');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1205, 'MA8',  '', 0, '', 'Province de Moulay Yacoub');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1205, 'MA9',  '', 0, '', 'Province de Sefrou');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1202, 'MA8A', '', 0, '', 'Province de Kénitra');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1202, 'MA9A', '', 0, '', 'Province de Sidi Kacem');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1202, 'MA10', '', 0, '', 'Province de Sidi Slimane');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1208, 'MA11', '', 0, '', 'Préfecture de Casablanca');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1208, 'MA12', '', 0, '', 'Préfecture de Mohammédia');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1208, 'MA13', '', 0, '', 'Province de Médiouna');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1208, 'MA14', '', 0, '', 'Province de Nouaceur');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1214, 'MA15', '', 0, '', 'Province d''Assa-Zag');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1214, 'MA16', '', 0, '', 'Province d''Es-Semara');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1214, 'MA17A','', 0, '', 'Province de Guelmim');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1214, 'MA18', '', 0, '', 'Province de Tata');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1214, 'MA19', '', 0, '', 'Province de Tan-Tan');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1215, 'MA15', '', 0, '', 'Province de Boujdour');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1215, 'MA16', '', 0, '', 'Province de Lâayoune');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1215, 'MA17', '', 0, '', 'Province de Tarfaya');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1211, 'MA18', '', 0, '', 'Préfecture de Marrakech');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1211, 'MA19', '', 0, '', 'Province d''Al Haouz');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1211, 'MA20', '', 0, '', 'Province de Chichaoua');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1211, 'MA21', '', 0, '', 'Province d''El Kelâa des Sraghna');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1211, 'MA22', '', 0, '', 'Province d''Essaouira');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1211, 'MA23', '', 0, '', 'Province de Rehamna');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1206, 'MA24', '', 0, '', 'Préfecture de Meknès');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1206, 'MA25', '', 0, '', 'Province d’El Hajeb');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1206, 'MA26', '', 0, '', 'Province d''Errachidia');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1206, 'MA27', '', 0, '', 'Province d’Ifrane');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1206, 'MA28', '', 0, '', 'Province de Khénifra');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1206, 'MA29', '', 0, '', 'Province de Midelt');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1204, 'MA30', '', 0, '', 'Préfecture d''Oujda-Angad');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1204, 'MA31', '', 0, '', 'Province de Berkane');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1204, 'MA32', '', 0, '', 'Province de Driouch');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1204, 'MA33', '', 0, '', 'Province de Figuig');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1204, 'MA34', '', 0, '', 'Province de Jerada');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1204, 'MA35', '', 0, '', 'Province de Nador');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1204, 'MA36', '', 0, '', 'Province de Taourirt');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1216, 'MA37', '', 0, '', 'Province d''Aousserd');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1216, 'MA38', '', 0, '', 'Province d''Oued Ed-Dahab');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1207, 'MA39', '', 0, '', 'Préfecture de Rabat');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1207, 'MA40', '', 0, '', 'Préfecture de Skhirat-Témara');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1207, 'MA41', '', 0, '', 'Préfecture de Salé');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1207, 'MA42', '', 0, '', 'Province de Khémisset');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1213, 'MA43', '', 0, '', 'Préfecture d''Agadir Ida-Outanane');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1213, 'MA44', '', 0, '', 'Préfecture d''Inezgane-Aït Melloul');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1213, 'MA45', '', 0, '', 'Province de Chtouka-Aït Baha');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1213, 'MA46', '', 0, '', 'Province d''Ouarzazate');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1213, 'MA47', '', 0, '', 'Province de Sidi Ifni');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1213, 'MA48', '', 0, '', 'Province de Taroudant');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1213, 'MA49', '', 0, '', 'Province de Tinghir');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1213, 'MA50', '', 0, '', 'Province de Tiznit');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1213, 'MA51', '', 0, '', 'Province de Zagora');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1212, 'MA52', '', 0, '', 'Province d''Azilal');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1212, 'MA53', '', 0, '', 'Province de Beni Mellal');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1212, 'MA54', '', 0, '', 'Province de Fquih Ben Salah');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1201, 'MA55', '', 0, '', 'Préfecture de M''diq-Fnideq');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1201, 'MA56', '', 0, '', 'Préfecture de Tanger-Asilah');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1201, 'MA57', '', 0, '', 'Province de Chefchaouen');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1201, 'MA58', '', 0, '', 'Province de Fahs-Anjra');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1201, 'MA59', '', 0, '', 'Province de Larache');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1201, 'MA60', '', 0, '', 'Province d''Ouezzane');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1201, 'MA61', '', 0, '', 'Province de Tétouan');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1203, 'MA62', '', 0, '', 'Province de Guercif');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1203, 'MA63', '', 0, '', 'Province d''Al Hoceïma');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1203, 'MA64', '', 0, '', 'Province de Taounate');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1203, 'MA65', '', 0, '', 'Province de Taza');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1205, 'MA6A', '', 0, '', 'Préfecture de Fès');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1205, 'MA7A', '', 0, '', 'Province de Boulemane');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1214, 'MA15A','', 0, '', 'Province d''Assa-Zag');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1214, 'MA16A','', 0, '', 'Province d''Es-Semara');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1211, 'MA18A','', 0, '', 'Préfecture de Marrakech');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1214, 'MA19A','', 0, '', 'Province de Tan-Tan');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1214, 'MA19B','', 0, '', 'Province de Tan-Tan');


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


-- Panama - 10 Provinces (id country=178)
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (17801, 'PA-1',  '', 0, '', 'Bocas del Toro');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (17801, 'PA-2',  '', 0, '', 'Coclé');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (17801, 'PA-3',  '', 0, '', 'Colón');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (17801, 'PA-4',  '', 0, '', 'Chiriquí');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (17801, 'PA-5',  '', 0, '', 'Darién');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (17801, 'PA-6',  '', 0, '', 'Herrera');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (17801, 'PA-7',  '', 0, '', 'Los Santos');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (17801, 'PA-8',  '', 0, '', 'Panamá');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (17801, 'PA-9',  '', 0, '', 'Veraguas');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (17801, 'PA-13', '', 0, '', 'Panamá Oeste');


-- Provinces Peru (id country=181)
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0101', 18101, '', 0, '', 'Chachapoyas');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0102', 18101, '', 0, '', 'Bagua');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0103', 18101, '', 0, '', 'Bongará');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0104', 18101, '', 0, '', 'Condorcanqui');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0105', 18101, '', 0, '', 'Luya');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0106', 18101, '', 0, '', 'Rodríguez de Mendoza');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0107', 18101, '', 0, '', 'Utcubamba');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0201', 18102, '', 0, '', 'Huaraz');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0202', 18102, '', 0, '', 'Aija');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0203', 18102, '', 0, '', 'Antonio Raymondi');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0204', 18102, '', 0, '', 'Asunción');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0205', 18102, '', 0, '', 'Bolognesi');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0206', 18102, '', 0, '', 'Carhuaz');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0207', 18102, '', 0, '', 'Carlos Fermín Fitzcarrald');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0208', 18102, '', 0, '', 'Casma');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0209', 18102, '', 0, '', 'Corongo');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0210', 18102, '', 0, '', 'Huari');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0211', 18102, '', 0, '', 'Huarmey');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0212', 18102, '', 0, '', 'Huaylas');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0213', 18102, '', 0, '', 'Mariscal Luzuriaga');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0214', 18102, '', 0, '', 'Ocros');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0215', 18102, '', 0, '', 'Pallasca');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0216', 18102, '', 0, '', 'Pomabamba');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0217', 18102, '', 0, '', 'Recuay');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0218', 18102, '', 0, '', 'Papá');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0219', 18102, '', 0, '', 'Sihuas');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0220', 18102, '', 0, '', 'Yungay');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0301', 18103, '', 0, '', 'Abancay');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0302', 18103, '', 0, '', 'Andahuaylas');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0303', 18103, '', 0, '', 'Antabamba');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0304', 18103, '', 0, '', 'Aymaraes');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0305', 18103, '', 0, '', 'Cotabambas');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0306', 18103, '', 0, '', 'Chincheros');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0307', 18103, '', 0, '', 'Grau');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0401', 18104, '', 0, '', 'Arequipa');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0402', 18104, '', 0, '', 'Camaná');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0403', 18104, '', 0, '', 'Caravelí');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0404', 18104, '', 0, '', 'Castilla');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0405', 18104, '', 0, '', 'Caylloma');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0406', 18104, '', 0, '', 'Condesuyos');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0407', 18104, '', 0, '', 'Islay');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0408', 18104, '', 0, '', 'La Unión');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0501', 18105, '', 0, '', 'Huamanga');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0502', 18105, '', 0, '', 'Cangallo');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0503', 18105, '', 0, '', 'Huanca Sancos');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0504', 18105, '', 0, '', 'Huanta');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0505', 18105, '', 0, '', 'La Mar');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0506', 18105, '', 0, '', 'Lucanas');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0507', 18105, '', 0, '', 'Parinacochas');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0508', 18105, '', 0, '', 'Páucar del Sara Sara');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0509', 18105, '', 0, '', 'Sucre');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0510', 18105, '', 0, '', 'Víctor Fajardo');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0511', 18105, '', 0, '', 'Vilcas Huamán');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0601', 18106, '', 0, '', 'Cajamarca');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0602', 18106, '', 0, '', 'Cajabamba');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0603', 18106, '', 0, '', 'Celendín');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0604', 18106, '', 0, '', 'Chota');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0605', 18106, '', 0, '', 'Contumazá');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0606', 18106, '', 0, '', 'Cutervo');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0607', 18106, '', 0, '', 'Hualgayoc');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0608', 18106, '', 0, '', 'Jaén');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0609', 18106, '', 0, '', 'San Ignacio');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0610', 18106, '', 0, '', 'San Marcos');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0611', 18106, '', 0, '', 'San Miguel');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0612', 18106, '', 0, '', 'San Pablo');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0613', 18106, '', 0, '', 'Santa Cruz');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0701', 18107, '', 0, '', 'Callao');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0801', 18108, '', 0, '', 'Cusco');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0802', 18108, '', 0, '', 'Acomayo');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0803', 18108, '', 0, '', 'Anta');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0804', 18108, '', 0, '', 'Calca');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0805', 18108, '', 0, '', 'Canas');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0806', 18108, '', 0, '', 'Canchis');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0807', 18108, '', 0, '', 'Chumbivilcas');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0808', 18108, '', 0, '', 'Espinar');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0809', 18108, '', 0, '', 'La Convención');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0810', 18108, '', 0, '', 'Paruro');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0811', 18108, '', 0, '', 'Paucartambo');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0812', 18108, '', 0, '', 'Quispicanchi');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0813', 18108, '', 0, '', 'Urubamba');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0901', 18109, '', 0, '', 'Huancavelica');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0902', 18109, '', 0, '', 'Acobamba');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0903', 18109, '', 0, '', 'Angaraes');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0904', 18109, '', 0, '', 'Castrovirreyna');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0905', 18109, '', 0, '', 'Churcampa');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0906', 18109, '', 0, '', 'Huaytará');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('0907', 18109, '', 0, '', 'Tayacaja');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1001', 18110, '', 0, '', 'Huánuco');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1002', 18110, '', 0, '', 'Ambón');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1003', 18110, '', 0, '', 'Dos de Mayo');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1004', 18110, '', 0, '', 'Huacaybamba');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1005', 18110, '', 0, '', 'Huamalíes');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1006', 18110, '', 0, '', 'Leoncio Prado');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1007', 18110, '', 0, '', 'Marañón');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1008', 18110, '', 0, '', 'Pachitea');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1009', 18110, '', 0, '', 'Puerto Inca');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1010', 18110, '', 0, '', 'Lauricocha');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1011', 18110, '', 0, '', 'Yarowilca');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1101', 18111, '', 0, '', 'Ica');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1102', 18111, '', 0, '', 'Chincha');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1103', 18111, '', 0, '', 'Nazca');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1104', 18111, '', 0, '', 'Palpa');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1105', 18111, '', 0, '', 'Pisco');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1201', 18112, '', 0, '', 'Huancayo');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1202', 18112, '', 0, '', 'Concepción');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1203', 18112, '', 0, '', 'Chanchamayo');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1204', 18112, '', 0, '', 'Jauja');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1205', 18112, '', 0, '', 'Junín');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1206', 18112, '', 0, '', 'Satipo');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1207', 18112, '', 0, '', 'Tarma');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1208', 18112, '', 0, '', 'Yauli');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1209', 18112, '', 0, '', 'Chupaca');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1301', 18113, '', 0, '', 'Trujillo');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1302', 18113, '', 0, '', 'Ascope');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1303', 18113, '', 0, '', 'Bolívar');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1304', 18113, '', 0, '', 'Chepén');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1305', 18113, '', 0, '', 'Julcán');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1306', 18113, '', 0, '', 'Otuzco');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1307', 18113, '', 0, '', 'Pacasmayo');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1308', 18113, '', 0, '', 'Pataz');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1309', 18113, '', 0, '', 'Sánchez Carrión');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1310', 18113, '', 0, '', 'Santiago de Chuco');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1311', 18113, '', 0, '', 'Gran Chimú');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1312', 18113, '', 0, '', 'Virú');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1401', 18114, '', 0, '', 'Chiclayo');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1402', 18114, '', 0, '', 'Ferreñafe');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1403', 18114, '', 0, '', 'Lambayeque');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1501', 18115, '', 0, '', 'Lima');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1502', 18116, '', 0, '', 'Huaura');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1503', 18116, '', 0, '', 'Barranca');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1504', 18116, '', 0, '', 'Cajatambo');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1505', 18116, '', 0, '', 'Canta');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1506', 18116, '', 0, '', 'Cañete');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1507', 18116, '', 0, '', 'Huaral');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1508', 18116, '', 0, '', 'Huarochirí');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1509', 18116, '', 0, '', 'Oyón');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1510', 18116, '', 0, '', 'Yauyos');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1601', 18117, '', 0, '', 'Maynas');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1602', 18117, '', 0, '', 'Alto Amazonas');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1603', 18117, '', 0, '', 'Loreto');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1604', 18117, '', 0, '', 'Mariscal Ramón Castilla');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1605', 18117, '', 0, '', 'Requena');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1606', 18117, '', 0, '', 'Ucayali');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1607', 18117, '', 0, '', 'Datem del Marañón');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1701', 18118, '', 0, '', 'Tambopata');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1702', 18118, '', 0, '', 'Manú');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1703', 18118, '', 0, '', 'Tahuamanu');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1801', 18119, '', 0, '', 'Mariscal Nieto');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1802', 18119, '', 0, '', 'General Sánchez Cerro');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1803', 18119, '', 0, '', 'Ilo');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1901', 18120, '', 0, '', 'Pasco');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1902', 18120, '', 0, '', 'Daniel Alcides Carrión');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('1903', 18120, '', 0, '', 'Oxapampa');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2001', 18121, '', 0, '', 'Piura');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2002', 18121, '', 0, '', 'Ayabaca');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2003', 18121, '', 0, '', 'Huancabamba');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2004', 18121, '', 0, '', 'Morropón');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2005', 18121, '', 0, '', 'Paita');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2006', 18121, '', 0, '', 'Sullana');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2007', 18121, '', 0, '', 'Talara');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2008', 18121, '', 0, '', 'Sechura');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2101', 18122, '', 0, '', 'Puno');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2102', 18122, '', 0, '', 'Azángaro');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2103', 18122, '', 0, '', 'Carabaya');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2104', 18122, '', 0, '', 'Chucuito');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2105', 18122, '', 0, '', 'El Collao');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2106', 18122, '', 0, '', 'Huancané');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2107', 18122, '', 0, '', 'Lampa');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2108', 18122, '', 0, '', 'Melgar');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2109', 18122, '', 0, '', 'Moho');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2110', 18122, '', 0, '', 'San Antonio de Putina');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2111', 18122, '', 0, '', 'San Román');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2112', 18122, '', 0, '', 'Sandia');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2113', 18122, '', 0, '', 'Yunguyo');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2201', 18123, '', 0, '', 'Moyobamba');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2202', 18123, '', 0, '', 'Bellavista');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2203', 18123, '', 0, '', 'El Dorado');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2204', 18123, '', 0, '', 'Huallaga');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2205', 18123, '', 0, '', 'Lamas');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2206', 18123, '', 0, '', 'Mariscal Cáceres');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2207', 18123, '', 0, '', 'Picota');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2208', 18123, '', 0, '', 'La Rioja');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2209', 18123, '', 0, '', 'San Martín');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2210', 18123, '', 0, '', 'Tocache');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2301', 18124, '', 0, '', 'Tacna');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2302', 18124, '', 0, '', 'Candarave');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2303', 18124, '', 0, '', 'Jorge Basadre');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2304', 18124, '', 0, '', 'Tarata');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2401', 18125, '', 0, '', 'Tumbes');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2402', 18125, '', 0, '', 'Contralmirante Villar');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2403', 18125, '', 0, '', 'Zarumilla');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2501', 18126, '', 0, '', 'Coronel Portillo');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2502', 18126, '', 0, '', 'Atalaya');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2503', 18126, '', 0, '', 'Padre Abad');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('2504', 18126, '', 0, '', 'Purús');


-- Portugal Provinces / Postal Districts (rowid country=25)
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (15001, 'PT-AV', NULL, NULL, 'AVEIRO', 'Aveiro');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (15002, 'PT-AC', NULL, NULL, 'AZORES', 'Azores');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (15001, 'PT-BE', NULL, NULL, 'BEJA', 'Beja');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (15001, 'PT-BR', NULL, NULL, 'BRAGA', 'Braga');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (15001, 'PT-BA', NULL, NULL, 'BRAGANCA', 'Bragança');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (15001, 'PT-CB', NULL, NULL, 'CASTELO BRANCO', 'Castelo Branco');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (15001, 'PT-CO', NULL, NULL, 'COIMBRA', 'Coimbra');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (15001, 'PT-EV', NULL, NULL, 'EVORA', 'Évora');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (15001, 'PT-FA', NULL, NULL, 'FARO', 'Faro');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (15001, 'PT-GU', NULL, NULL, 'GUARDA', 'Guarda');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (15001, 'PT-LE', NULL, NULL, 'LEIRIA', 'Leiria');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (15001, 'PT-LI', NULL, NULL, 'LISBON', 'Lisboa');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (15001, 'PT-AML',NULL, NULL, 'AREA METROPOLITANA LISBOA', 'Área Metropolitana de Lisboa');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (15002, 'PT-MA', NULL, NULL, 'MADEIRA', 'Madeira');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (15001, 'PT-PA', NULL, NULL, 'PORTALEGRE', 'Portalegre');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (15001, 'PT-PO', NULL, NULL, 'PORTO', 'Porto');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (15001, 'PT-SA', NULL, NULL, 'SANTAREM', 'Santarém');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (15001, 'PT-SE', NULL, NULL, 'SETUBAL', 'Setúbal');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (15001, 'PT-VC', NULL, NULL, 'VIANA DO CASTELO', 'Viana Do Castelo');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (15001, 'PT-VR', NULL, NULL, 'VILA REAL', 'Vila Real');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (15001, 'PT-VI', NULL, NULL, 'VISEU', 'Viseu');


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


-- San Salvador / El Salvador Departmentos (id country=86)
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (8601, 'SS', '', 0, '', 'San Salvador');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (8601, 'LL', '', 0, '', 'La Libertad');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (8601, 'CH', '', 0, '', 'Chalatenango');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (8601, 'CA', '', 0, '', 'Cabañas');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (8601, 'LP', '', 0, '', 'La Paz');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (8601, 'SV', '', 0, '', 'San Vicente');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (8601, 'CU', '', 0, '', 'Cuscatlan');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (8602, 'US', '', 0, '', 'Usulutan');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (8602, 'SM', '', 0, '', 'San Miguel');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (8602, 'MO', '', 0, '', 'Morazan');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (8602, 'LU', '', 0, '', 'La Union');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (8603, 'AH', '', 0, '', 'Ahuachapan');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (8603, 'SA', '', 0, '', 'Santa Ana');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (8603, 'SO', '', 0, '', 'Sonsonate');


-- Slovenia Provinces (rowid country=202)
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (20203, 'SI031', NULL, NULL, 'MURA', 'Mura');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (20203, 'SI032', NULL, NULL, 'DRAVA', 'Drava');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (20203, 'SI033', NULL, NULL, 'CARINTHIA', 'Carinthia');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (20203, 'SI034', NULL, NULL, 'SAVINJA', 'Savinja');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (20203, 'SI035', NULL, NULL, 'CENTRAL SAVA', 'Central Sava');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (20203, 'SI036', NULL, NULL, 'LOWER SAVA', 'Lower Sava');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (20203, 'SI037', NULL, NULL, 'SOUTHEAST SLOVENIA', 'Southeast Slovenia');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (20203, 'SI038', NULL, NULL, 'LITTORAL–INNER CARNIOLA', 'Littoral–Inner Carniola');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (20204, 'SI041', NULL, NULL, 'CENTRAL SLOVENIA', 'Central Slovenia');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (20204, 'SI038', NULL, NULL, 'UPPER CARNIOLA', 'Upper Carniola');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (20204, 'SI043', NULL, NULL, 'GORIZIA', 'Gorizia');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (20204, 'SI044', NULL, NULL, 'COASTAL–KARST', 'Coastal–Karst');


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


-- Taiwan Divisions / Provinces / Counties (id country=213)
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


-- Tunisia Governorates / Provinces / Wilaya (id country=10)
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1001, 'TN01', '', 0, '', 'Ariana');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1001, 'TN02', '', 0, '', 'Béja');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1001, 'TN03', '', 0, '', 'Ben Arous');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1001, 'TN04', '', 0, '', 'Bizerte');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1001, 'TN05', '', 0, '', 'Gabès');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1001, 'TN06', '', 0, '', 'Gafsa');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1001, 'TN07', '', 0, '', 'Jendouba');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1001, 'TN08', '', 0, '', 'Kairouan');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1001, 'TN09', '', 0, '', 'Kasserine');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1001, 'TN10', '', 0, '', 'Kébili');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1001, 'TN11', '', 0, '', 'La Manouba');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1001, 'TN12', '', 0, '', 'Le Kef');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1001, 'TN13', '', 0, '', 'Mahdia');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1001, 'TN14', '', 0, '', 'Médenine');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1001, 'TN15', '', 0, '', 'Monastir');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1001, 'TN16', '', 0, '', 'Nabeul');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1001, 'TN17', '', 0, '', 'Sfax');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1001, 'TN18', '', 0, '', 'Sidi Bouzid');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1001, 'TN19', '', 0, '', 'Siliana');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1001, 'TN20', '', 0, '', 'Sousse');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1001, 'TN21', '', 0, '', 'Tataouine');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1001, 'TN22', '', 0, '', 'Tozeur');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1001, 'TN23', '', 0, '', 'Tunis');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (1001, 'TN24', '', 0, '', 'Zaghouan');


-- USA States (id country=11)
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'AL', '', 0, 'ALABAMA', 'Alabama');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'AK', '', 0, 'ALASKA', 'Alaska');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'AZ', '', 0, 'ARIZONA', 'Arizona');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'AR', '', 0, 'ARKANSAS', 'Arkansas');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'CA', '', 0, 'CALIFORNIA', 'California');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'CO', '', 0, 'COLORADO', 'Colorado');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'CT', '', 0, 'CONNECTICUT', 'Connecticut');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'DE', '', 0, 'DELAWARE', 'Delaware');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'FL', '', 0, 'FLORIDA', 'Florida');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'GA', '', 0, 'GEORGIA', 'Georgia');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'HI', '', 0, 'HAWAII', 'Hawaii');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'ID', '', 0, 'IDAHO', 'Idaho');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'IL', '', 0, 'ILLINOIS','Illinois');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'IN', '', 0, 'INDIANA', 'Indiana');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'IA', '', 0, 'IOWA', 'Iowa');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'KS', '', 0, 'KANSAS', 'Kansas');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'KY', '', 0, 'KENTUCKY', 'Kentucky');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'LA', '', 0, 'LOUISIANA', 'Louisiana');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'ME', '', 0, 'MAINE', 'Maine');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'MD', '', 0, 'MARYLAND', 'Maryland');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'MA', '', 0, 'MASSACHUSSETTS', 'Massachusetts');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'MI', '', 0, 'MICHIGAN', 'Michigan');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'MN', '', 0, 'MINNESOTA', 'Minnesota');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'MS', '', 0, 'MISSISSIPPI', 'Mississippi');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'MO', '', 0, 'MISSOURI', 'Missouri');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'MT', '', 0, 'MONTANA', 'Montana');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'NE', '', 0, 'NEBRASKA', 'Nebraska');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'NV', '', 0, 'NEVADA', 'Nevada');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'NH', '', 0, 'NEW HAMPSHIRE', 'New Hampshire');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'NJ', '', 0, 'NEW JERSEY', 'New Jersey');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'NM', '', 0, 'NEW MEXICO', 'New Mexico');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'NY', '', 0, 'NEW YORK', 'New York');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'NC', '', 0, 'NORTH CAROLINA', 'North Carolina');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'ND', '', 0, 'NORTH DAKOTA', 'North Dakota');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'OH', '', 0, 'OHIO', 'Ohio');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'OK', '', 0, 'OKLAHOMA', 'Oklahoma');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'OR', '', 0, 'OREGON', 'Oregon');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'PA', '', 0, 'PENNSYLVANIA', 'Pennsylvania');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'RI', '', 0, 'RHODE ISLAND', 'Rhode Island');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'SC', '', 0, 'SOUTH CAROLINA', 'South Carolina');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'SD', '', 0, 'SOUTH DAKOTA', 'South Dakota');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'TN', '', 0, 'TENNESSEE', 'Tennessee');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'TX', '', 0, 'TEXAS', 'Texas');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'UT', '', 0, 'UTAH', 'Utah');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'VT', '', 0, 'VERMONT', 'Vermont');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'VA', '', 0, 'VIRGINIA', 'Virginia');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'WA', '', 0, 'WASHINGTON', 'Washington');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'WV', '', 0, 'WEST VIRGINIA', 'West Virginia');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'WI', '', 0, 'WISCONSIN', 'Wisconsin');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values (1101, 'WY', '', 0, 'WYOMING', 'Wyoming');





-- Provinces Bolivia (id country=52)
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('001', 5201, '', 0, '', 'Belisario Boeto');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('002', 5201, '', 0, '', 'Hernando Siles');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('003', 5201, '', 0, '', 'Jaime Zudáñez');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('004', 5201, '', 0, '', 'Juana Azurduy de Padilla');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('005', 5201, '', 0, '', 'Luis Calvo');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('006', 5201, '', 0, '', 'Nor Cinti');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('007', 5201, '', 0, '', 'Oropeza');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('008', 5201, '', 0, '', 'Sud Cinti');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('009', 5201, '', 0, '', 'Tomina');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('010', 5201, '', 0, '', 'Yamparáez');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('011', 5202, '', 0, '', 'Abel Iturralde');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('012', 5202, '', 0, '', 'Aroma');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('013', 5202, '', 0, '', 'Bautista Saavedra');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('014', 5202, '', 0, '', 'Caranavi');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('015', 5202, '', 0, '', 'Eliodoro Camacho');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('016', 5202, '', 0, '', 'Franz Tamayo');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('017', 5202, '', 0, '', 'Gualberto Villarroel');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('018', 5202, '', 0, '', 'Ingaví');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('019', 5202, '', 0, '', 'Inquisivi');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('020', 5202, '', 0, '', 'José Ramón Loayza');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('021', 5202, '', 0, '', 'Larecaja');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('022', 5202, '', 0, '', 'Los Andes (Bolivia)');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('023', 5202, '', 0, '', 'Manco Kapac');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('024', 5202, '', 0, '', 'Muñecas');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('025', 5202, '', 0, '', 'Nor Yungas');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('026', 5202, '', 0, '', 'Omasuyos');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('027', 5202, '', 0, '', 'Pacajes');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('028', 5202, '', 0, '', 'Pedro Domingo Murillo');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('029', 5202, '', 0, '', 'Sud Yungas');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('030', 5202, '', 0, '', 'General José Manuel Pando');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('031', 5203, '', 0, '', 'Arani');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('032', 5203, '', 0, '', 'Arque');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('033', 5203, '', 0, '', 'Ayopaya');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('034', 5203, '', 0, '', 'Bolívar (Bolivia)');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('035', 5203, '', 0, '', 'Campero');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('036', 5203, '', 0, '', 'Capinota');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('037', 5203, '', 0, '', 'Cercado (Cochabamba)');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('038', 5203, '', 0, '', 'Esteban Arze');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('039', 5203, '', 0, '', 'Germán Jordán');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('040', 5203, '', 0, '', 'José Carrasco');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('041', 5203, '', 0, '', 'Mizque');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('042', 5203, '', 0, '', 'Punata');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('043', 5203, '', 0, '', 'Quillacollo');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('044', 5203, '', 0, '', 'Tapacarí');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('045', 5203, '', 0, '', 'Tiraque');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('046', 5203, '', 0, '', 'Chapare');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('047', 5204, '', 0, '', 'Carangas');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('048', 5204, '', 0, '', 'Cercado (Oruro)');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('049', 5204, '', 0, '', 'Eduardo Avaroa');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('050', 5204, '', 0, '', 'Ladislao Cabrera');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('051', 5204, '', 0, '', 'Litoral de Atacama');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('052', 5204, '', 0, '', 'Mejillones');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('053', 5204, '', 0, '', 'Nor Carangas');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('054', 5204, '', 0, '', 'Pantaleón Dalence');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('055', 5204, '', 0, '', 'Poopó');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('056', 5204, '', 0, '', 'Sabaya');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('057', 5204, '', 0, '', 'Sajama');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('058', 5204, '', 0, '', 'San Pedro de Totora');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('059', 5204, '', 0, '', 'Saucarí');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('060', 5204, '', 0, '', 'Sebastián Pagador');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('061', 5204, '', 0, '', 'Sud Carangas');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('062', 5204, '', 0, '', 'Tomás Barrón');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('063', 5205, '', 0, '', 'Alonso de Ibáñez');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('064', 5205, '', 0, '', 'Antonio Quijarro');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('065', 5205, '', 0, '', 'Bernardino Bilbao');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('066', 5205, '', 0, '', 'Charcas (Potosí)');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('067', 5205, '', 0, '', 'Chayanta');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('068', 5205, '', 0, '', 'Cornelio Saavedra');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('069', 5205, '', 0, '', 'Daniel Campos');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('070', 5205, '', 0, '', 'Enrique Baldivieso');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('071', 5205, '', 0, '', 'José María Linares');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('072', 5205, '', 0, '', 'Modesto Omiste');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('073', 5205, '', 0, '', 'Nor Chichas');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('074', 5205, '', 0, '', 'Nor Lípez');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('075', 5205, '', 0, '', 'Rafael Bustillo');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('076', 5205, '', 0, '', 'Sud Chichas');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('077', 5205, '', 0, '', 'Sud Lípez');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('078', 5205, '', 0, '', 'Tomás Frías');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('079', 5206, '', 0, '', 'Aniceto Arce');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('080', 5206, '', 0, '', 'Burdet O''Connor');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('081', 5206, '', 0, '', 'Cercado (Tarija)');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('082', 5206, '', 0, '', 'Eustaquio Méndez');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('083', 5206, '', 0, '', 'José María Avilés');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('084', 5206, '', 0, '', 'Gran Chaco');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('085', 5207, '', 0, '', 'Andrés Ibáñez');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('086', 5207, '', 0, '', 'Caballero');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('087', 5207, '', 0, '', 'Chiquitos');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('088', 5207, '', 0, '', 'Cordillera (Bolivia)');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('089', 5207, '', 0, '', 'Florida');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('090', 5207, '', 0, '', 'Germán Busch');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('091', 5207, '', 0, '', 'Guarayos');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('092', 5207, '', 0, '', 'Ichilo');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('093', 5207, '', 0, '', 'Obispo Santistevan');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('094', 5207, '', 0, '', 'Sara');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('095', 5207, '', 0, '', 'Vallegrande');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('096', 5207, '', 0, '', 'Velasco');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('097', 5207, '', 0, '', 'Warnes');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('098', 5207, '', 0, '', 'Ángel Sandóval');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('099', 5207, '', 0, '', 'Ñuflo de Chaves');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('100', 5208, '', 0, '', 'Cercado (Beni)');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('101', 5208, '', 0, '', 'Iténez');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('102', 5208, '', 0, '', 'Mamoré');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('103', 5208, '', 0, '', 'Marbán');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('104', 5208, '', 0, '', 'Moxos');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('105', 5208, '', 0, '', 'Vaca Díez');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('106', 5208, '', 0, '', 'Yacuma');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('107', 5208, '', 0, '', 'General José Ballivián Segurola');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('108', 5209, '', 0, '', 'Abuná');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('109', 5209, '', 0, '', 'Madre de Dios');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('110', 5209, '', 0, '', 'Manuripi');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('111', 5209, '', 0, '', 'Nicolás Suárez');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('112', 5209, '', 0, '', 'General Federico Román');


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


-- Provinces GB (id country=7)
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('701', 701, NULL, 0,NULL, 'Bedfordshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('702', 701, NULL, 0,NULL, 'Berkshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('703', 701, NULL, 0,NULL, 'Bristol, City of');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('704', 701, NULL, 0,NULL, 'Buckinghamshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('705', 701, NULL, 0,NULL, 'Cambridgeshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('706', 701, NULL, 0,NULL, 'Cheshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('707', 701, NULL, 0,NULL, 'Cleveland');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('708', 701, NULL, 0,NULL, 'Cornwall');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('709', 701, NULL, 0,NULL, 'Cumberland');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('710', 701, NULL, 0,NULL, 'Cumbria');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('711', 701, NULL, 0,NULL, 'Derbyshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('712', 701, NULL, 0,NULL, 'Devon');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('713', 701, NULL, 0,NULL, 'Dorset');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('714', 701, NULL, 0,NULL, 'Co. Durham');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('715', 701, NULL, 0,NULL, 'East Riding of Yorkshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('716', 701, NULL, 0,NULL, 'East Sussex');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('717', 701, NULL, 0,NULL, 'Essex');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('718', 701, NULL, 0,NULL, 'Gloucestershire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('719', 701, NULL, 0,NULL, 'Greater Manchester');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('720', 701, NULL, 0,NULL, 'Hampshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('721', 701, NULL, 0,NULL, 'Hertfordshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('722', 701, NULL, 0,NULL, 'Hereford and Worcester');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('723', 701, NULL, 0,NULL, 'Herefordshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('724', 701, NULL, 0,NULL, 'Huntingdonshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('725', 701, NULL, 0,NULL, 'Isle of Man');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('726', 701, NULL, 0,NULL, 'Isle of Wight');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('727', 701, NULL, 0,NULL, 'Jersey');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('728', 701, NULL, 0,NULL, 'Kent');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('729', 701, NULL, 0,NULL, 'Lancashire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('730', 701, NULL, 0,NULL, 'Leicestershire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('731', 701, NULL, 0,NULL, 'Lincolnshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('732', 701, NULL, 0,NULL, 'London - City of London');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('733', 701, NULL, 0,NULL, 'Merseyside');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('734', 701, NULL, 0,NULL, 'Middlesex');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('735', 701, NULL, 0,NULL, 'Norfolk');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('736', 701, NULL, 0,NULL, 'North Yorkshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('737', 701, NULL, 0,NULL, 'North Riding of Yorkshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('738', 701, NULL, 0,NULL, 'Northamptonshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('739', 701, NULL, 0,NULL, 'Northumberland');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('740', 701, NULL, 0,NULL, 'Nottinghamshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('741', 701, NULL, 0,NULL, 'Oxfordshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('742', 701, NULL, 0,NULL, 'Rutland');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('743', 701, NULL, 0,NULL, 'Shropshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('744', 701, NULL, 0,NULL, 'Somerset');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('745', 701, NULL, 0,NULL, 'Staffordshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('746', 701, NULL, 0,NULL, 'Suffolk');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('747', 701, NULL, 0,NULL, 'Surrey');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('748', 701, NULL, 0,NULL, 'Sussex');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('749', 701, NULL, 0,NULL, 'Tyne and Wear');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('750', 701, NULL, 0,NULL, 'Warwickshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('751', 701, NULL, 0,NULL, 'West Midlands');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('752', 701, NULL, 0,NULL, 'West Sussex');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('753', 701, NULL, 0,NULL, 'West Yorkshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('754', 701, NULL, 0,NULL, 'West Riding of Yorkshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('755', 701, NULL, 0,NULL, 'Wiltshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('756', 701, NULL, 0,NULL, 'Worcestershire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('757', 701, NULL, 0,NULL, 'Yorkshire');
-- Wales
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('758', 702, NULL, 0,NULL, 'Anglesey');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('759', 702, NULL, 0,NULL, 'Breconshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('760', 702, NULL, 0,NULL, 'Caernarvonshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('761', 702, NULL, 0,NULL, 'Cardiganshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('762', 702, NULL, 0,NULL, 'Carmarthenshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('763', 702, NULL, 0,NULL, 'Ceredigion');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('764', 702, NULL, 0,NULL, 'Denbighshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('765', 702, NULL, 0,NULL, 'Flintshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('766', 702, NULL, 0,NULL, 'Glamorgan');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('767', 702, NULL, 0,NULL, 'Gwent');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('768', 702, NULL, 0,NULL, 'Gwynedd');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('769', 702, NULL, 0,NULL, 'Merionethshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('770', 702, NULL, 0,NULL, 'Monmouthshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('771', 702, NULL, 0,NULL, 'Mid Glamorgan');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('772', 702, NULL, 0,NULL, 'Montgomeryshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('773', 702, NULL, 0,NULL, 'Pembrokeshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('774', 702, NULL, 0,NULL, 'Powys');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('775', 702, NULL, 0,NULL, 'Radnorshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('776', 702, NULL, 0,NULL, 'South Glamorgan');
-- Scotland
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('777', 703, NULL, 0,NULL, 'Aberdeen, City of');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('778', 703, NULL, 0,NULL, 'Angus');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('779', 703, NULL, 0,NULL, 'Argyll');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('780', 703, NULL, 0,NULL, 'Ayrshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('781', 703, NULL, 0,NULL, 'Banffshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('782', 703, NULL, 0,NULL, 'Berwickshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('783', 703, NULL, 0,NULL, 'Bute');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('784', 703, NULL, 0,NULL, 'Caithness');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('785', 703, NULL, 0,NULL, 'Clackmannanshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('786', 703, NULL, 0,NULL, 'Dumfriesshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('787', 703, NULL, 0,NULL, 'Dumbartonshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('788', 703, NULL, 0,NULL, 'Dundee, City of');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('789', 703, NULL, 0,NULL, 'East Lothian');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('790', 703, NULL, 0,NULL, 'Fife');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('791', 703, NULL, 0,NULL, 'Inverness');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('792', 703, NULL, 0,NULL, 'Kincardineshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('793', 703, NULL, 0,NULL, 'Kinross-shire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('794', 703, NULL, 0,NULL, 'Kirkcudbrightshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('795', 703, NULL, 0,NULL, 'Lanarkshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('796', 703, NULL, 0,NULL, 'Midlothian');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('797', 703, NULL, 0,NULL, 'Morayshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('798', 703, NULL, 0,NULL, 'Nairnshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('799', 703, NULL, 0,NULL, 'Orkney');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('800', 703, NULL, 0,NULL, 'Peebleshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('801', 703, NULL, 0,NULL, 'Perthshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('802', 703, NULL, 0,NULL, 'Renfrewshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('803', 703, NULL, 0,NULL, 'Ross & Cromarty');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('804', 703, NULL, 0,NULL, 'Roxburghshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('805', 703, NULL, 0,NULL, 'Selkirkshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('806', 703, NULL, 0,NULL, 'Shetland');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('807', 703, NULL, 0,NULL, 'Stirlingshire');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('808', 703, NULL, 0,NULL, 'Sutherland');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('809', 703, NULL, 0,NULL, 'West Lothian');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('810', 703, NULL, 0,NULL, 'Wigtownshire');
-- Northern Ireland
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('811', 704, NULL, 0,NULL, 'Antrim');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('812', 704, NULL, 0,NULL, 'Armagh');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('813', 704, NULL, 0,NULL, 'Co. Down');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('814', 704, NULL, 0,NULL, 'Co. Fermanagh');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('815', 704, NULL, 0,NULL, 'Co. Londonderry');


-- Provinces India (id country=117)
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('AN', 11701, NULL, 0, 'AN', 'Andaman & Nicobar');    
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('AP', 11701, NULL, 0, 'AP', 'Andhra Pradesh');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('AR', 11701, NULL, 0, 'AR', 'Arunachal Pradesh');     
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('AS', 11701, NULL, 0, 'AS', 'Assam');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('BR', 11701, NULL, 0, 'BR', 'Bihar');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('CG', 11701, NULL, 0, 'CG', 'Chattisgarh');     
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('CH', 11701, NULL, 0, 'CH', 'Chandigarh');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('DD', 11701, NULL, 0, 'DD', 'Daman & Diu');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('DL', 11701, NULL, 0, 'DL', 'Delhi');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('DN', 11701, NULL, 0, 'DN', 'Dadra and Nagar Haveli');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('GA', 11701, NULL, 0, 'GA', 'Goa');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('GJ', 11701, NULL, 0, 'GJ', 'Gujarat');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('HP', 11701, NULL, 0, 'HP', 'Himachal Pradesh');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('HR', 11701, NULL, 0, 'HR', 'Haryana');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('JH', 11701, NULL, 0, 'JH', 'Jharkhand');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('JK', 11701, NULL, 0, 'JK', 'Jammu & Kashmir');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('KA', 11701, NULL, 0, 'KA', 'Karnataka');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('KL', 11701, NULL, 0, 'KL', 'Kerala');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('LD', 11701, NULL, 0, 'LD', 'Lakshadweep');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('MH', 11701, NULL, 0, 'MH', 'Maharashtra');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('ML', 11701, NULL, 0, 'ML', 'Meghalaya');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('MN', 11701, NULL, 0, 'MN', 'Manipur');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('MP', 11701, NULL, 0, 'MP', 'Madhya Pradesh');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('MZ', 11701, NULL, 0, 'MZ', 'Mizoram');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('NL', 11701, NULL, 0, 'NL', 'Nagaland');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('OR', 11701, NULL, 0, 'OR', 'Orissa');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('PB', 11701, NULL, 0, 'PB', 'Punjab');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('PY', 11701, NULL, 0, 'PY', 'Puducherry');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('RJ', 11701, NULL, 0, 'RJ', 'Rajasthan');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('SK', 11701, NULL, 0, 'SK', 'Sikkim');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('TE', 11701, NULL, 0, 'TE', 'Telangana');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('TN', 11701, NULL, 0, 'TN', 'Tamil Nadu');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('TR', 11701, NULL, 0, 'TR', 'Tripura');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('UL', 11701, NULL, 0, 'UL', 'Uttarakhand');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('UP', 11701, NULL, 0, 'UP', 'Uttar Pradesh');
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('WB', 11701, NULL, 0, 'WB', 'West Bengal');

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


-- Venezuela Provinces (id country=232)
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


-- Burundi Communes (id country=61) -- https://fr.wikipedia.org/wiki/Communes_du_Burundi
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6101, 'BI0001', '', 0, '', 'Bubanza');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6101, 'BI0002', '', 0, '', 'Gihanga');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6101, 'BI0003', '', 0, '', 'Musigati');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6101, 'BI0004', '', 0, '', 'Mpanda');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6101, 'BI0005', '', 0, '', 'Rugazi');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6102, 'BI0006', '', 0, '', 'Muha');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6102, 'BI0007', '', 0, '', 'Mukaza');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6102, 'BI0008', '', 0, '', 'Ntahangwa');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6103, 'BI0009', '', 0, '', 'Isale');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6103, 'BI0010', '', 0, '', 'Kabezi');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6103, 'BI0011', '', 0, '', 'Kanyosha');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6103, 'BI0012', '', 0, '', 'Mubimbi');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6103, 'BI0013', '', 0, '', 'Mugongomanga');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6103, 'BI0014', '', 0, '', 'Mukike');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6103, 'BI0015', '', 0, '', 'Mutambu');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6103, 'BI0016', '', 0, '', 'Mutimbuzi');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6103, 'BI0017', '', 0, '', 'Nyabiraba');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6104, 'BI0018', '', 0, '', 'Bururi');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6104, 'BI0019', '', 0, '', 'Matana');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6104, 'BI0020', '', 0, '', 'Mugamba');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6104, 'BI0021', '', 0, '', 'Rutovu');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6104, 'BI0022', '', 0, '', 'Songa');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6104, 'BI0023', '', 0, '', 'Vyanda');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6105, 'BI0024', '', 0, '', 'Cankuzo');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6105, 'BI0025', '', 0, '', 'Cendajuru');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6105, 'BI0026', '', 0, '', 'Gisagara');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6105, 'BI0027', '', 0, '', 'Kigamba');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6105, 'BI0028', '', 0, '', 'Mishiha');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6106, 'BI0029', '', 0, '', 'Buganda');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6106, 'BI0030', '', 0, '', 'Bukinanyana');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6106, 'BI0031', '', 0, '', 'Mabayi');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6106, 'BI0032', '', 0, '', 'Mugina');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6106, 'BI0033', '', 0, '', 'Murwi');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6106, 'BI0034', '', 0, '', 'Rugombo');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6107, 'BI0035', '', 0, '', 'Bugendana');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6107, 'BI0036', '', 0, '', 'Bukirasazi');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6107, 'BI0037', '', 0, '', 'Buraza');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6107, 'BI0038', '', 0, '', 'Giheta');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6107, 'BI0039', '', 0, '', 'Gishubi');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6107, 'BI0040', '', 0, '', 'Gitega');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6107, 'BI0041', '', 0, '', 'Itaba');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6107, 'BI0042', '', 0, '', 'Makebuko');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6107, 'BI0043', '', 0, '', 'Mutaho');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6107, 'BI0044', '', 0, '', 'Nyanrusange');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6107, 'BI0045', '', 0, '', 'Ryansoro');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6108, 'BI0046', '', 0, '', 'Bugenyuzi');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6108, 'BI0047', '', 0, '', 'Buhiga');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6108, 'BI0048', '', 0, '', 'Gihogazi');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6108, 'BI0049', '', 0, '', 'Gitaramuka');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6108, 'BI0050', '', 0, '', 'Mutumba');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6108, 'BI0051', '', 0, '', 'Nyabikere');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6108, 'BI0052', '', 0, '', 'Shombo');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6109, 'BI0053', '', 0, '', 'Butaganzwa');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6109, 'BI0054', '', 0, '', 'Gahombo');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6109, 'BI0055', '', 0, '', 'Gatara');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6109, 'BI0056', '', 0, '', 'Kabarore');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6109, 'BI0057', '', 0, '', 'Kayanza');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6109, 'BI0058', '', 0, '', 'Matongo');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6109, 'BI0059', '', 0, '', 'Muhanga');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6109, 'BI0060', '', 0, '', 'Muruta');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6109, 'BI0061', '', 0, '', 'Rango');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6110, 'BI0062', '', 0, '', 'Bugabira');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6110, 'BI0063', '', 0, '', 'Busoni');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6110, 'BI0064', '', 0, '', 'Bwambarangwe');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6110, 'BI0065', '', 0, '', 'Gitobe');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6110, 'BI0066', '', 0, '', 'Kirundo');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6110, 'BI0067', '', 0, '', 'Ntega');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6110, 'BI0068', '', 0, '', 'Vumbi');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6111, 'BI0069', '', 0, '', 'Kayogoro');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6111, 'BI0070', '', 0, '', 'Kibago');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6111, 'BI0071', '', 0, '', 'Mabanda');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6111, 'BI0072', '', 0, '', 'Makamba');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6111, 'BI0073', '', 0, '', 'Nyanza-Lac');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6111, 'BI0074', '', 0, '', 'Vugizo');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6112, 'BI0075', '', 0, '', 'Bukeye');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6112, 'BI0076', '', 0, '', 'Kiganda');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6112, 'BI0077', '', 0, '', 'Mbuye');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6112, 'BI0078', '', 0, '', 'Muramvya');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6112, 'BI0079', '', 0, '', 'Rutegama');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6113, 'BI0080', '', 0, '', 'Buhinyuza');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6113, 'BI0081', '', 0, '', 'Butihinda');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6113, 'BI0082', '', 0, '', 'Gashoho');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6113, 'BI0083', '', 0, '', 'Gasorwe');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6113, 'BI0084', '', 0, '', 'Giteranyi');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6113, 'BI0085', '', 0, '', 'Muyinga');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6113, 'BI0086', '', 0, '', 'Mwakiro');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6114, 'BI0087', '', 0, '', 'Bisoro');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6114, 'BI0088', '', 0, '', 'Gisozi');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6114, 'BI0089', '', 0, '', 'Kayokwe');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6114, 'BI0090', '', 0, '', 'Ndava');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6114, 'BI0091', '', 0, '', 'Nyabihanga');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6114, 'BI0092', '', 0, '', 'Rusaka');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6115, 'BI0093', '', 0, '', 'Busiga');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6115, 'BI0094', '', 0, '', 'Gashikanwa');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6115, 'BI0095', '', 0, '', 'Kiremba');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6115, 'BI0096', '', 0, '', 'Marangara');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6115, 'BI0097', '', 0, '', 'Mwumba');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6115, 'BI0098', '', 0, '', 'Ngozi');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6115, 'BI0099', '', 0, '', 'Nyamurenza');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6115, 'BI0100', '', 0, '', 'Ruhororo');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6115, 'BI0101', '', 0, '', 'Tangara');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6116, 'BI0102', '', 0, '', 'Bugarama');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6116, 'BI0103', '', 0, '', 'Burambi');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6116, 'BI0104', '', 0, '', 'Buyengero');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6116, 'BI0105', '', 0, '', 'Muhuta');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6116, 'BI0106', '', 0, '', 'Rumonge');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6117, 'BI0107', '', 0, '', 'Bukemba');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6117, 'BI0108', '', 0, '', 'Giharo');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6117, 'BI0109', '', 0, '', 'Gitanga');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6117, 'BI0110', '', 0, '', 'Mpinga-Kayove');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6117, 'BI0111', '', 0, '', 'Musongati');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6117, 'BI0112', '', 0, '', 'Rutana');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6118, 'BI0113', '', 0, '', 'Butaganzwa');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6118, 'BI0114', '', 0, '', 'Butezi');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6118, 'BI0115', '', 0, '', 'Bweru');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6118, 'BI0116', '', 0, '', 'Gisuru');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6118, 'BI0117', '', 0, '', 'Kinyinya');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6118, 'BI0118', '', 0, '', 'Nyabitsinda');
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES (6118, 'BI0119', '', 0, '', 'Ruyigi');


-- Provinces United Arab Emirates (id country=227)
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('AE-1', 22701, '', 0, '', 'Abu Dhabi');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('AE-2', 22701, '', 0, '', 'Dubai');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('AE-3', 22701, '', 0, '', 'Ajman');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('AE-4', 22701, '', 0, '', 'Fujairah');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('AE-5', 22701, '', 0, '', 'Ras al-Khaimah');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('AE-6', 22701, '', 0, '', 'Sharjah');
INSERT INTO llx_c_departements (code_departement, fk_region, cheflieu, tncc, ncc, nom) VALUES ('AE-7', 22701, '', 0, '', 'Umm al-Quwain');


-- Turkiye (Turkey)  (id country=221)
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-01',22104,'Adana');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-02',22107,'Adıyaman');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-03',22103,'Afyon');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-04',22107,'Ağrı');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-05',22106,'Amasya');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-06',22102,'Ankara');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-07',22104,'Antalya');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-08',22106,'Artvin');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-09',22103,'Aydın');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-10',22101,'Balıkesir');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-11',22101,'Bilecik');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-12',22107,'Bingöl');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-13',22107,'Bitlis');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-14',22106,'Bolu');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-15',22104,'Burdur');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-16',22101,'Bursa');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-17',22101,'Çanakkale');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-18',22102,'Çankırı');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-19',22106,'Çorum');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-20',22104,'Denizli');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-21',22105,'Diyarbakır');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-22',22101,'Edirne');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-23',22107,'Elazığ');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-24',22107,'Erzincan');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-25',22107,'Erzurum');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-26',22102,'Eskişehir');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-27',22105,'Gaziantep');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-28',22106,'Giresun');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-29',22106,'Gümüşhane');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-30',22107,'Hakkari');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-31',22104,'Hatay');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-32',22104,'Isparta');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-33',22104,'İçel');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-34',22101,'İstanbul');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-35',22103,'İzmir');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-36',22107,'Kars');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-37',22106,'Kastamonu');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-38',22102,'Kayseri');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-39',22101,'Kırklareli');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-40',22102,'Kırşehir');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-41',22101,'Kocaeli');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-42',22102,'Konya');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-43',22103,'Kütahya');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-44',22107,'Malatya');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-45',22103,'Manisa');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-46',22104,'Kahramanmaraş');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-47',22105,'Mardin');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-48',22103,'Muğla');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-49',22107,'Muş');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-50',22102,'Nevşehir');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-51',22102,'Niğde');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-52',22106,'Ordu');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-53',22106,'Rize');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-54',22101,'Sakarya');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-55',22106,'Samsun');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-56',22105,'Siirt');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-57',22106,'Sinop');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-58',22102,'Sivas');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-59',22101,'Tekirdağ');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-60',22106,'Tokat');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-61',22106,'Trabzon');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-62',22107,'Tunceli');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-63',22105,'Şanlıurfa');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-63',22103,'Uşak');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-65',22107,'Van');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-66',22102,'Yozgat');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-67',22106,'Zonguldak');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-68',22102,'Aksaray');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-69',22106,'Bayburt');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-70',22102,'Karaman');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-71',22102,'Kırıkkale');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-72',22105,'Batman');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-73',22105,'Şırnak');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-74',22106,'Bartın');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-75',22107,'Ardahan');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-76',22107,'Iğdır');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-77',22101,'Yalova');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-78',22106,'Karabük');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-79',22105,'Kilis');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-80',22104,'Osmaniye');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('TR-81',22106,'Düzce');

-- Provinces Cuba (id country=77)
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('CU-PRI', 7701, 'Pinar del Rio');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('CU-ART', 7701, 'Artemisa');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('CU-HAB', 7701, 'La Habana');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('CU-MYB', 7701, 'Mayabeque');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('CU-MTZ', 7701, 'Matanzas');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('CU-IJV', 7701, 'Isla de la Juventud');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('CU-VLC', 7702, 'Villa Calra');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('CU-CFG', 7702, 'Cienfuegos');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('CU-SSP', 7702, 'Sancti Spiritus');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('CU-CAV', 7702, 'Ciego de Avila');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('CU-CMG', 7702, 'Camagüey');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('CU-LTU', 7703, 'Las Tunas');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('CU-GRM', 7703, 'Granma');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('CU-SCU', 7703, 'Santiago de Cuba');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('CU-GTM', 7703, 'Guantanamo');
INSERT INTO llx_c_departements (code_departement, fk_region, nom) VALUES ('CU-HLG', 7703, 'Holguin');
