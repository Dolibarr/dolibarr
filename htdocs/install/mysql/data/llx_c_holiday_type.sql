-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
-- Copyright (C) 2012 	   Tommaso Basilici       <t.basilici@19.coop>
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
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

-- Generic to all countries
insert into llx_c_holiday_types(code, label, affect, delay, newByMonth, fk_country, active) values ('LEAVE_SICK',    'Sick leave',    0, 0, 0,    NULL, 1);
insert into llx_c_holiday_types(code, label, affect, delay, newByMonth, fk_country, active) values ('LEAVE_OTHER',   'Other leave',   0, 0, 0,    NULL, 1);

-- Not enabled by default, we prefer to have an entrey dedicated to country
insert into llx_c_holiday_types(code, label, affect, delay, newByMonth, fk_country, active) values ('LEAVE_PAID',    'Paid vacation', 1, 7, 0,    NULL, 0);

-- Leaves specific to France
insert into llx_c_holiday_types(code, label, affect, delay, newByMonth, fk_country, active) values ('LEAVE_RTT_FR',  'RTT'          , 1,  7, 0.83,    1, 1);
insert into llx_c_holiday_types(code, label, affect, delay, newByMonth, fk_country, active) values ('LEAVE_PAID_FR', 'Paid vacation', 1, 30, 2.08334, 1, 1);
