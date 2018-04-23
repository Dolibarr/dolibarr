-- Copyright (C) 2016 		Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
-- Copyright (C) 2017 		Laurent Destailleur  <eldy@users.sourceforge.net>
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


-- Group of accounting accounts for report. This is a minimal default setup.
INSERT INTO llx_c_accounting_category (rowid, code, label, range_account, sens, category_type, formula, position, fk_country, active) VALUES (  1, 'INCOMES',   'Income of products/services',               'Example: 7xxxxx', 0, 0, '',                 '10', 0, 1);
INSERT INTO llx_c_accounting_category (rowid, code, label, range_account, sens, category_type, formula, position, fk_country, active) VALUES (  2, 'EXPENSES',  'Expenses of products/services',             'Example: 6xxxxx', 0, 0, '',                 '20', 0, 1);
INSERT INTO llx_c_accounting_category (rowid, code, label, range_account, sens, category_type, formula, position, fk_country, active) VALUES (  3, 'PROFIT',    'Balance',                                   '',                0, 1, 'INCOMES+EXPENSES', '30', 0, 1);

