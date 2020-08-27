-- ============================================================================
-- Copyright (C) 2014      Cédric GROSS         <c.gross@kreiz-it.fr>
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
CREATE TABLE llx_expeditiondet_batch (
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  fk_expeditiondet int NOT NULL,
  eatby date DEFAULT NULL,
  sellby date DEFAULT NULL,
  batch varchar(128) DEFAULT NULL,
  qty double NOT NULL DEFAULT '0',
  fk_origin_stock integer NOT NULL
) ENGINE=innodb;

