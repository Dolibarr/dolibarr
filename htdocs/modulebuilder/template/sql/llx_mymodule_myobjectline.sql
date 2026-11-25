-- Copyright (C) ---Replace with your own copyright and developer email---
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



-- Table of lines of object MyObject (only if the object manages lines)
CREATE TABLE llx_mymodule_myobjectline(
	rowid INTEGER AUTO_INCREMENT PRIMARY KEY NOT NULL,
	fk_myobject INTEGER NOT NULL,
	fk_product INTEGER,
	description TEXT,
	qty DOUBLE(24,8) DEFAULT 1 NOT NULL,
	rang INTEGER DEFAULT 0 NOT NULL,
	date_creation DATETIME NOT NULL,
	tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_creat INTEGER NOT NULL,
	fk_user_modif INTEGER,
	import_key VARCHAR(14)
) ENGINE=innodb;
