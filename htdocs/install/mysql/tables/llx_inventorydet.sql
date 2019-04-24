-- ===================================================================
-- Copyright (C) 2012      Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2017	ATM Consulting		<support@atm-consulting.fr>
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
-- ===================================================================

CREATE TABLE llx_inventorydet 
( 
rowid integer NOT NULL AUTO_INCREMENT PRIMARY KEY, 
datec datetime DEFAULT NULL,
tms timestamp, 
fk_inventory integer DEFAULT 0, 
fk_warehouse integer DEFAULT 0,
fk_product integer DEFAULT 0,  
batch varchar(30) DEFAULT NULL,	 -- Lot or serial number
qty_view double DEFAULT NULL, 	     -- must be filled once regulation is done
qty_stock double DEFAULT NULL,   -- can be filled during draft edition
qty_regulated double DEFAULT NULL  -- must be filled once regulation is done
) 
ENGINE=InnoDB;
