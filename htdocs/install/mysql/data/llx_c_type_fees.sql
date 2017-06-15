-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
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

INSERT INTO llx_c_type_fees (code, label, active, accountancy_code) VALUES
('EX_KME', 'ExpLabelKm', 0, '625100'),
('EX_FUE', 'ExpLabelFuelCV', 0, '606150'),
('EX_HOT', 'ExpLabelHotel', 0, '625160'),
('EX_PAR', 'ExpLabelParkingCV', 0, '625160'),
('EX_TOL', 'ExpLabelTollCV', 0, '625160'),
('EX_TAX', 'ExpLabelVariousTaxes', 0, '637800'),
('EX_IND', 'ExpLabelIndemnityTransportationSubscription', 0, '648100'),
('EX_SUM', 'ExpLabelMaintenanceSupply', 0, '606300'),
('EX_SUO', 'ExpLabelOfficeSupplies', 0, '606400'),
('EX_CAR', 'ExpLabelCarRental', 0, '613000'),
('EX_DOC', 'ExpLabelDocumentation', 0, '618100'),
('EX_CUR', 'ExpLabelCustomersReceiving', 0, '625710'),
('EX_OTR', 'ExpLabelOtherReceiving', 0, '625700'),
('EX_POS', 'ExpLabelPostage', 0, '626100'),
('EX_CAM', 'ExpLabelMaintenanceRepairCV', 0, '615300'),
('EX_EMM', 'ExpLabelEmployeesMeal', 0, '625160'),
('EX_GUM', 'ExpLabelGuestsMeal', 0, '625160'),
('EX_BRE', 'ExpLabelBreakfast', 0, '625160'),
('EX_FUE_VP', 'ExpLabelFuelPV', 0, '606150'),
('EX_TOL_VP', 'ExpLabelTollPV', 0, '625160'),
('EX_PAR_VP', 'ExpLabelParkingPV', 0, '625160'),
('EX_CAM_VP', 'ExpLabelMaintenanceRepairPV', 0, '615300');