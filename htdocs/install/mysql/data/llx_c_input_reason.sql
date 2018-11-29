-- Copyright (C) 2011-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2011      Philippe Grand
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

INSERT INTO llx_c_input_reason (rowid,code,label,active) VALUES ( 1, 'SRC_INTE',       'Web site', 1);
INSERT INTO llx_c_input_reason (rowid,code,label,active) VALUES ( 2, 'SRC_CAMP_MAIL',  'Mailing campaign', 1);
INSERT INTO llx_c_input_reason (rowid,code,label,active) VALUES ( 3, 'SRC_CAMP_PHO',   'Phone campaign', 1);
INSERT INTO llx_c_input_reason (rowid,code,label,active) VALUES ( 4, 'SRC_CAMP_FAX',   'Fax campaign', 1);
INSERT INTO llx_c_input_reason (rowid,code,label,active) VALUES ( 5, 'SRC_COMM',       'Commercial contact', 1);
INSERT INTO llx_c_input_reason (rowid,code,label,active) VALUES ( 6, 'SRC_SHOP',       'Shop contact', 1);
INSERT INTO llx_c_input_reason (rowid,code,label,active) VALUES ( 7, 'SRC_CAMP_EMAIL', 'EMailing campaign', 1);
INSERT INTO llx_c_input_reason (rowid,code,label,active) VALUES ( 8, 'SRC_WOM',        'Word of mouth', 1);
INSERT INTO llx_c_input_reason (rowid,code,label,active) VALUES ( 9, 'SRC_PARTNER',    'Partner', 1);
INSERT INTO llx_c_input_reason (rowid,code,label,active) VALUES (10, 'SRC_EMPLOYEE',   'Employee', 1);
INSERT INTO llx_c_input_reason (rowid,code,label,active) VALUES (11, 'SRC_SPONSORING', 'Sponsorship', 1);
INSERT INTO llx_c_input_reason (rowid,code,label,active) VALUES (12, 'SRC_CUSTOMER',   'Incoming contact of a customer', 1);
