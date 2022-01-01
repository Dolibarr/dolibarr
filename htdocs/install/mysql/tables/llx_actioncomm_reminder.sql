-- Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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


CREATE TABLE llx_actioncomm_reminder(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	dateremind datetime NOT NULL, 
	typeremind varchar(32) NOT NULL, 
	fk_user integer NOT NULL, 
	offsetvalue integer NOT NULL, 
	offsetunit varchar(1) NOT NULL,
	status integer NOT NULL DEFAULT 0
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
