-- ============================================================================
-- Copyright (C) 2020      John BOTELLA         <john.botella@atm-consulting.fr>
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
-- ============================================================================

CREATE TABLE llx_product_attribute_combination_price_level
(
  rowid INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  fk_product_attribute_combination INTEGER DEFAULT 1 NOT NULL,
  fk_price_level INTEGER DEFAULT 1 NOT NULL,
  variation_price DOUBLE(24,8) NOT NULL,
  variation_price_percentage INTEGER NULL
)ENGINE=innodb;

