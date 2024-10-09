-- ===================================================================
-- Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
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
-- Table for connect accounting category with accounting account
-- ===================================================================

CREATE TABLE llx_accounting_category_account
(
  rowid           			integer AUTO_INCREMENT PRIMARY KEY,
  fk_accounting_category	integer,
  fk_accounting_account		bigint
) ENGINE=innodb;
