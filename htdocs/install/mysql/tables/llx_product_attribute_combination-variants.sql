-- ============================================================================
-- Copyright (C) 2016      Marcos García         <marcosgdf@gmail.com>
-- Copyright (C) 2020      Laurent Destailleur   <eldy@users.sourceforge.net>
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
-- Table to store links between a parent product and its variant products.
-- ============================================================================

CREATE TABLE llx_product_attribute_combination
(
  rowid INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  fk_product_parent INTEGER NOT NULL,					-- id of product id that is parent product
  fk_product_child INTEGER NOT NULL,					-- id of product id that is variant (child) product
  variation_price DOUBLE(24,8) NOT NULL,
  variation_price_percentage INTEGER NULL,
  variation_weight REAL NOT NULL,
  variation_ref_ext VARCHAR(255) NULL,
  entity INTEGER DEFAULT 1 NOT NULL
)ENGINE=innodb;
