-- ============================================================================
-- Copyright (C) 2016      Marcos García         <marcosgdf@gmail.com>
-- Copyright (C) 2017      Laurent Destailleur   <eldy@users.sourceforge.net>
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
-- llx_product_attribute_value is table for different available values of a product variants attributes.
-- For example BLUE, GREEN, ... for the product attribute COLOR.
-- ============================================================================

CREATE TABLE llx_product_attribute_value
(
  rowid					INTEGER			PRIMARY KEY NOT NULL AUTO_INCREMENT,
  fk_product_attribute	INTEGER			NOT NULL,
  ref					VARCHAR(180)	NOT NULL,
  value					VARCHAR(255)	NOT NULL,
  entity				INTEGER			DEFAULT 1 NOT NULL,
  position				INTEGER			NOT NULL DEFAULT 0
)ENGINE=innodb;
