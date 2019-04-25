-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
-- Copyright (C) 2017      ATM Consulting       <contact@atm-consulting.fr>
-- Copyright (C) 2017      Pierre-Henry Favre   <phf@atm-consulting.fr>
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

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

--
-- Type fees
--

insert into llx_c_type_fees (code,label,active) values ('TF_OTHER',    'Other',  1);
insert into llx_c_type_fees (code,label,active) values ('TF_TRIP',     'Transportation',   1);
insert into llx_c_type_fees (code,label,active) values ('TF_LUNCH',    'Lunch',  1);


INSERT INTO llx_c_type_fees (code, label, active) VALUES('EX_KME',    'ExpLabelKm', 1);
INSERT INTO llx_c_type_fees (code, label, active) VALUES('EX_FUE',    'ExpLabelFuelCV', 0);
INSERT INTO llx_c_type_fees (code, label, active) VALUES('EX_HOT',    'ExpLabelHotel', 0);
INSERT INTO llx_c_type_fees (code, label, active) VALUES('EX_PAR',    'ExpLabelParkingCV', 0);
INSERT INTO llx_c_type_fees (code, label, active) VALUES('EX_TOL',    'ExpLabelTollCV', 0);
INSERT INTO llx_c_type_fees (code, label, active) VALUES('EX_TAX',    'ExpLabelVariousTaxes', 0);
INSERT INTO llx_c_type_fees (code, label, active) VALUES('EX_IND',    'ExpLabelIndemnityTransSubscrip', 0);
INSERT INTO llx_c_type_fees (code, label, active) VALUES('EX_SUM',    'ExpLabelMaintenanceSupply', 0);
INSERT INTO llx_c_type_fees (code, label, active) VALUES('EX_SUO',    'ExpLabelOfficeSupplies', 0);
INSERT INTO llx_c_type_fees (code, label, active) VALUES('EX_CAR',    'ExpLabelCarRental', 0);
INSERT INTO llx_c_type_fees (code, label, active) VALUES('EX_DOC',    'ExpLabelDocumentation', 0);
INSERT INTO llx_c_type_fees (code, label, active) VALUES('EX_CUR',    'ExpLabelCustomersReceiving', 0);
INSERT INTO llx_c_type_fees (code, label, active) VALUES('EX_OTR',    'ExpLabelOtherReceiving', 0);
INSERT INTO llx_c_type_fees (code, label, active) VALUES('EX_POS',    'ExpLabelPostage', 0);
INSERT INTO llx_c_type_fees (code, label, active) VALUES('EX_CAM',    'ExpLabelMaintenanceRepairCV', 0);
INSERT INTO llx_c_type_fees (code, label, active) VALUES('EX_EMM',    'ExpLabelEmployeesMeal', 0);
INSERT INTO llx_c_type_fees (code, label, active) VALUES('EX_GUM',    'ExpLabelGuestsMeal', 0);
INSERT INTO llx_c_type_fees (code, label, active) VALUES('EX_BRE',    'ExpLabelBreakfast', 0);
INSERT INTO llx_c_type_fees (code, label, active) VALUES('EX_FUE_VP', 'ExpLabelFuelPV', 0);
INSERT INTO llx_c_type_fees (code, label, active) VALUES('EX_TOL_VP', 'ExpLabelTollPV', 0);
INSERT INTO llx_c_type_fees (code, label, active) VALUES('EX_PAR_VP', 'ExpLabelParkingPV', 0);
INSERT INTO llx_c_type_fees (code, label, active) VALUES('EX_CAM_VP', 'ExpLabelMaintenanceRepairPV', 0);

-- Set accoutancy_code for french accounting plan
--UPDATE llx_c_type_fees SET accountancy_code = '625100' WHERE code = 'EX_KME';
--UPDATE llx_c_type_fees SET accountancy_code = '606150' WHERE code = 'EX_FUE';
--UPDATE llx_c_type_fees SET accountancy_code = '625160' WHERE code = 'EX_HOT';
--UPDATE llx_c_type_fees SET accountancy_code = '625160' WHERE code = 'EX_PAR';
--UPDATE llx_c_type_fees SET accountancy_code = '625160' WHERE code = 'EX_TOL';
--UPDATE llx_c_type_fees SET accountancy_code = '637800' WHERE code = 'EX_TAX';
--UPDATE llx_c_type_fees SET accountancy_code = '648100' WHERE code = 'EX_IND';
--UPDATE llx_c_type_fees SET accountancy_code = '606300' WHERE code = 'EX_SUM';
--UPDATE llx_c_type_fees SET accountancy_code = '606400' WHERE code = 'EX_SUO';
--UPDATE llx_c_type_fees SET accountancy_code = '613000' WHERE code = 'EX_CAR';
--UPDATE llx_c_type_fees SET accountancy_code = '618100' WHERE code = 'EX_DOC';
--UPDATE llx_c_type_fees SET accountancy_code = '625710' WHERE code = 'EX_CUR';
--UPDATE llx_c_type_fees SET accountancy_code = '625700' WHERE code = 'EX_OTR';
--UPDATE llx_c_type_fees SET accountancy_code = '626100' WHERE code = 'EX_POS';
--UPDATE llx_c_type_fees SET accountancy_code = '615300' WHERE code = 'EX_CAM';
--UPDATE llx_c_type_fees SET accountancy_code = '625160' WHERE code = 'EX_EMM';
--UPDATE llx_c_type_fees SET accountancy_code = '625160' WHERE code = 'EX_GUM';
--UPDATE llx_c_type_fees SET accountancy_code = '625160' WHERE code = 'EX_BRE';
--UPDATE llx_c_type_fees SET accountancy_code = '606150' WHERE code = 'EX_FUE_VP';
--UPDATE llx_c_type_fees SET accountancy_code = '625160' WHERE code = 'EX_TOL_VP';
--UPDATE llx_c_type_fees SET accountancy_code = '625160' WHERE code = 'EX_PAR_VP';
--UPDATE llx_c_type_fees SET accountancy_code = '615300' WHERE code = 'EX_CAM_VP';
