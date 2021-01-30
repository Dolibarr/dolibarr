-- Copyright (C) 2019      Laurent Destailleur  <eldy@users.sourceforge.net>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see http://www.gnu.org/licenses/.


CREATE TABLE llx_mrp_production(
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	fk_mo integer NOT NULL,
	position integer NOT NULL DEFAULT 0,
	fk_product integer NOT NULL, 
	fk_warehouse integer,
	qty real NOT NULL DEFAULT 1,
	qty_frozen smallint DEFAULT 0,
    disable_stock_change smallint DEFAULT 0,
	batch varchar(128),
	role varchar(10),      			-- 'toconsume' or 'toproduce' (initialized at MO creation), 'consumed' or 'produced' (added after MO validation)
	fk_mrp_production integer,		-- if role = 'consumed', id of line with role 'toconsume', if role = 'produced' id of line with role 'toproduce'
	fk_stock_movement integer,		-- id of stock movement when movements are validated
	date_creation datetime NOT NULL, 
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer, 
	import_key varchar(14)
) ENGINE=innodb;

