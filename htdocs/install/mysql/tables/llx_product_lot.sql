-- ============================================================================
-- Copyright (C) 2016      Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- This table is dedicated to store lots with detail of each lot. Key is fk_product-batch is unique.
-- ============================================================================

CREATE TABLE llx_product_lot (
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  entity          integer DEFAULT 1,
  fk_product      integer NOT NULL,				-- Id of product
  batch           varchar(30) DEFAULT NULL,		-- Lot or serial number
  eatby           date DEFAULT NULL,			-- Eatby date
  sellby          date DEFAULT NULL, 			-- Sellby date
  datec         datetime,
  tms           timestamp,
  fk_user_creat integer,
  fk_user_modif integer,
  import_key    integer
) ENGINE=InnoDB;
