-- ============================================================================
-- Copyright (C) 2014      Cédric GROSS         <c.gross@kreiz-it.fr>
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
-- This table is dedicated to store detail (lots/serial) of a stock
-- ============================================================================

CREATE TABLE llx_product_batch (
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  tms timestamp,
  fk_product_stock integer NOT NULL,
  eatby datetime DEFAULT NULL,			-- deprecated. should not be used here but should be stored into a table llx_product_lot
  sellby datetime DEFAULT NULL,			-- deprecated. should not be used here but should be stored into a table llx_product_lot
  batch varchar(128) NOT NULL,
  qty double NOT NULL DEFAULT 0,
  import_key varchar(14) DEFAULT NULL
) ENGINE=innodb;

