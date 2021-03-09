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


CREATE TABLE llx_mymodule_myobject(
	-- BEGIN MODULEBUILDER FIELDS
	rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
	entity INTEGER DEFAULT 1 NOT NULL,
	label VARCHAR(255),
	qty INTEGER,
	date_creation DATETIME NOT NULL,
	tms TIMESTAMP,
	fk_user_create INTEGER,
	fk_user_modif INTEGER,
	status INTEGER,
	import_key VARCHAR(14)
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
