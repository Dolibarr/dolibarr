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
-- Copyright (C) 2020-2021  Udo Tamm               <dev@dolibit.de>
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
-- Croatia
-- France
-- Germany
-- Honduras
-- Hungary
-- Italy
-- Luxembourg
-- Netherlands
-- Morocco
-- Panama
-- Peru
-- Portugal
-- Romania
-- San Salvador -> El Salvador 
-- Slovenia (need to check code SI-Id)
-- Switzerland / Suisse
-- Taiwan
-- Tunisia
-- United States of America 
--
-- others .. unsorted  




-- TEMPLATE -------------------------------------------------------------------------------------------------------------
INSERT INTO llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) VALUES ( 0, '0',  '0',0,'-','-');
-- active is always set as on = 1


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
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values ( 1, '971','97105',3,'GUADELOUPE','Guadeloupe');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values ( 2, '972','97209',3,'MARTINIQUE','Martinique');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values ( 3, '973','97302',3,'GUYANE','Guyane');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values ( 4, '974','97411',3,'REUNION','Réunion');
insert into llx_c_departements (fk_region, code_departement, cheflieu, tncc, ncc, nom) values ( 6, '976','97601',3,'MAYOTTE','Mayotte');

insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (84,'01','01053',5,'AIN','Ain');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (32,'02','02408',5,'AISNE','Aisne');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (84,'03','03190',5,'ALLIER','Allier');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (93,'04','04070',4,'ALPES-DE-HAUTE-PROVENCE','Alpes-de-Haute-Provence');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (93,'05','05061',4,'HAUTES-ALPES','Hautes-Alpes');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (93,'06','06088',4,'ALPES-MARITIMES','Alpes-Maritimes');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (84,'07','07186',5,'ARDECHE','Ardèche');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (44,'08','08105',4,'ARDENNES','Ardennes');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (76,'09','09122',5,'ARIEGE','Ariège');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (44,'10','10387',5,'AUBE','Aube');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (76,'11','11069',5,'AUDE','Aude');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (76,'12','12202',5,'AVEYRON','Aveyron');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (93,'13','13055',4,'BOUCHES-DU-RHONE','Bouches-du-Rhône');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (28,'14','14118',2,'CALVADOS','Calvados');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (84,'15','15014',2,'CANTAL','Cantal');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (75,'16','16015',3,'CHARENTE','Charente');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (75,'17','17300',3,'CHARENTE-MARITIME','Charente-Maritime');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (24,'18','18033',2,'CHER','Cher');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (75,'19','19272',3,'CORREZE','Corrèze');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (94,'2A','2A004',3,'CORSE-DU-SUD','Corse-du-Sud');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (94,'2B','2B033',3,'HAUTE-CORSE','Haute-Corse');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (27,'21','21231',3,'COTE-D OR','Côte-d Or');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (53,'22','22278',4,'COTES-D ARMOR','Côtes-d Armor');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (75,'23','23096',3,'CREUSE','Creuse');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (75,'24','24322',3,'DORDOGNE','Dordogne');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (27,'25','25056',2,'DOUBS','Doubs');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (84,'26','26362',3,'DROME','Drôme');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (28,'27','27229',5,'EURE','Eure');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (24,'28','28085',1,'EURE-ET-LOIR','Eure-et-Loir');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (53,'29','29232',2,'FINISTERE','Finistère');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (76,'30','30189',2,'GARD','Gard');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (76,'31','31555',3,'HAUTE-GARONNE','Haute-Garonne');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (76,'32','32013',2,'GERS','Gers');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (75,'33','33063',3,'GIRONDE','Gironde');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (76,'34','34172',5,'HERAULT','Hérault');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (53,'35','35238',1,'ILLE-ET-VILAINE','Ille-et-Vilaine');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (24,'36','36044',5,'INDRE','Indre');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (24,'37','37261',1,'INDRE-ET-LOIRE','Indre-et-Loire');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (84,'38','38185',5,'ISERE','Isère');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (27,'39','39300',2,'JURA','Jura');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (75,'40','40192',4,'LANDES','Landes');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (24,'41','41018',0,'LOIR-ET-CHER','Loir-et-Cher');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (84,'42','42218',3,'LOIRE','Loire');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (84,'43','43157',3,'HAUTE-LOIRE','Haute-Loire');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (52,'44','44109',3,'LOIRE-ATLANTIQUE','Loire-Atlantique');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (24,'45','45234',2,'LOIRET','Loiret');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (76,'46','46042',2,'LOT','Lot');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (75,'47','47001',0,'LOT-ET-GARONNE','Lot-et-Garonne');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (76,'48','48095',3,'LOZERE','Lozère');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (52,'49','49007',0,'MAINE-ET-LOIRE','Maine-et-Loire');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (28,'50','50502',3,'MANCHE','Manche');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (44,'51','51108',3,'MARNE','Marne');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (44,'52','52121',3,'HAUTE-MARNE','Haute-Marne');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (52,'53','53130',3,'MAYENNE','Mayenne');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (44,'54','54395',0,'MEURTHE-ET-MOSELLE','Meurthe-et-Moselle');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (44,'55','55029',3,'MEUSE','Meuse');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (53,'56','56260',2,'MORBIHAN','Morbihan');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (44,'57','57463',3,'MOSELLE','Moselle');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (27,'58','58194',3,'NIEVRE','Nièvre');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (32,'59','59350',2,'NORD','Nord');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (32,'60','60057',5,'OISE','Oise');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (28,'61','61001',5,'ORNE','Orne');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (32,'62','62041',2,'PAS-DE-CALAIS','Pas-de-Calais');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (84,'63','63113',2,'PUY-DE-DOME','Puy-de-Dôme');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (75,'64','64445',4,'PYRENEES-ATLANTIQUES','Pyrénées-Atlantiques');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (76,'65','65440',4,'HAUTES-PYRENEES','Hautes-Pyrénées');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (76,'66','66136',4,'PYRENEES-ORIENTALES','Pyrénées-Orientales');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (44,'67','67482',2,'BAS-RHIN','Bas-Rhin');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (44,'68','68066',2,'HAUT-RHIN','Haut-Rhin');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (84,'69','69123',2,'RHONE','Rhône');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (27,'70','70550',3,'HAUTE-SAONE','Haute-Saône');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (27,'71','71270',0,'SAONE-ET-LOIRE','Saône-et-Loire');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (52,'72','72181',3,'SARTHE','Sarthe');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (84,'73','73065',3,'SAVOIE','Savoie');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (84,'74','74010',3,'HAUTE-SAVOIE','Haute-Savoie');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (11,'75','75056',0,'PARIS','Paris');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (28,'76','76540',3,'SEINE-MARITIME','Seine-Maritime');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (11,'77','77288',0,'SEINE-ET-MARNE','Seine-et-Marne');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (11,'78','78646',4,'YVELINES','Yvelines');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (75,'79','79191',4,'DEUX-SEVRES','Deux-Sèvres');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (32,'80','80021',3,'SOMME','Somme');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (76,'81','81004',2,'TARN','Tarn');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (76,'82','82121',0,'TARN-ET-GARONNE','Tarn-et-Garonne');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (93,'83','83137',2,'VAR','Var');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (93,'84','84007',0,'VAUCLUSE','Vaucluse');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (52,'85','85191',3,'VENDEE','Vendée');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (75,'86','86194',3,'VIENNE','Vienne');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (75,'87','87085',3,'HAUTE-VIENNE','Haute-Vienne');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (44,'88','88160',4,'VOSGES','Vosges');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (27,'89','89024',5,'YONNE','Yonne');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (27,'90','90010',0,'TERRITOIRE DE BELFORT','Territoire de Belfort');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (11,'91','91228',5,'ESSONNE','Essonne');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (11,'92','92050',4,'HAUTS-DE-SEINE','Hauts-de-Seine');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (11,'93','93008',3,'SEINE-SAINT-DENIS','Seine-Saint-Denis');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (11,'94','94028',2,'VAL-DE-MARNE','Val-de-Marne');
insert into llx_c_departements (fk_region, code_departement, cheflieu,tncc,ncc,nom) values (11,'95','95500',2,'VAL-D OISE','Val-d Oise');


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


-- Taiwan Divisions / Provinces / Counties (rowid country=886)
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
