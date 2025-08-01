-- Copyright (C) ---Put here your own copyright and developer email---
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
-- along with this program.  If not, see https://www.gnu.org/licenses/.

CREATE TABLE llx_bom_bomline(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	fk_bom integer NOT NULL,
	fk_product integer NOT NULL,
	fk_bom_child integer NULL,
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	description text,
	import_key varchar(14),
	qty double(24,8) NOT NULL,
    qty_frozen smallint DEFAULT 0,
    disable_stock_change smallint DEFAULT 0,
	efficiency double(24,8) NOT NULL DEFAULT 1,
	fk_unit integer NULL,
	position integer NOT NULL DEFAULT 0,
	fk_default_workstation integer DEFAULT NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
