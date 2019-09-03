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
-- along with this program.  If not, see http://www.gnu.org/licenses/.

CREATE TABLE llx_bom_bomline(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	fk_bom integer NOT NULL, 
	fk_product integer NOT NULL,
	fk_bom_child integer NULL,
	description text, 
	import_key varchar(14), 
	qty double(24,8) NOT NULL, 
	efficiency double(8,4) NOT NULL DEFAULT 1,
	position integer NOT NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
