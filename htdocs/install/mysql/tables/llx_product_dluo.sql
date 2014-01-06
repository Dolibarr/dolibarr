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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ============================================================================
CREATE TABLE `llx_product_dluo` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_product_stock` int(11) NOT NULL,
  `dluo` datetime DEFAULT NULL,
  `dlc` datetime DEFAULT NULL,
  `lot` varchar(30) DEFAULT NULL,
  `qty` double NOT NULL DEFAULT 0,
  `import_key` varchar(14) DEFAULT NULL
) ENGINE=InnoDB;
