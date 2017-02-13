-- ===================================================================
-- Copyright (C) 2015-2016 Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
-- Copyright (C) 2016	   Jamal Elbaz			<jamelbaz@gmail.pro>
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
-- Table with category for accounting account
-- ===================================================================

CREATE TABLE llx_c_accounting_category (
  rowid 			integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code 				varchar(16) NOT NULL,
  label 			varchar(255) NOT NULL,
  range_account		varchar(255) NOT NULL,
  sens 				tinyint NOT NULL DEFAULT '0',    -- For international accounting  0 : credit - debit / 1 : debit - credit
  category_type		tinyint NOT NULL DEFAULT '0',    -- Field calculated or not
  formula			varchar(255) NOT NULL,			 -- Example : 1 + 2 (rowid of the category)
  position    		integer DEFAULT 0,
  fk_country 		integer DEFAULT NULL,			 -- This category is dedicated to a country
  active 			integer DEFAULT 1
) ENGINE=innodb;
