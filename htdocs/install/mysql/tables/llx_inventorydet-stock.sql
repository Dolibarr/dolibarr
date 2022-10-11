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
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
-- ===================================================================

CREATE TABLE llx_inventorydet 
( 
	rowid integer NOT NULL AUTO_INCREMENT PRIMARY KEY, 
	datec datetime DEFAULT NULL,
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
	fk_inventory integer DEFAULT 0, 
	fk_warehouse integer DEFAULT 0,
	fk_product integer DEFAULT 0,  
	batch varchar(128) DEFAULT NULL,   -- Lot or serial number
	qty_stock double DEFAULT NULL,     -- Value or real stock we have, when we start the inventory (may be updated during intermediary steps).
	qty_view double DEFAULT NULL, 	   -- Quantity found during inventory. It is the targeted value, filled during edition of inventory.
	qty_regulated double DEFAULT NULL, -- Never used. Deprecated because we already have the fk_movement now.
	pmp_real double DEFAULT NULL,
	pmp_expected double DEFAULT NULL,
	fk_movement integer NULL           -- can contain the id of stock movement we recorded to make the inventory regulation of this line
) 
ENGINE=innodb;
