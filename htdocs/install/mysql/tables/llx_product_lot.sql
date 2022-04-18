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
  batch           varchar(128) DEFAULT NULL,	-- Lot or serial number
  eatby           date DEFAULT NULL,			-- Eatby date
  sellby          date DEFAULT NULL, 			-- Sellby date
  eol_date      datetime NULL,
  manufacturing_date datetime NULL,                -- date when first manufacturing of this lot has started 
  scrapping_date datetime NULL,                    -- date when we decided to scrap all products of this lot
  barcode       varchar(180) DEFAULT NULL,         -- barcode
  fk_barcode_type   integer DEFAULT NULL,          -- barcode type
  datec         datetime,
  tms           timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat integer,
  fk_user_modif integer,
  import_key    integer
) ENGINE=innodb;
