-- Copyright (C) 2022 Alice Adminson <aadminson@example.com>
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


CREATE TABLE llx_bookcal_availabilities(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	label varchar(255), 
	description text, 
	note_public text, 
	note_private text, 
	date_creation datetime NOT NULL, 
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer, 
	last_main_doc varchar(255), 
	import_key varchar(14), 
	model_pdf varchar(255), 
	status integer NOT NULL, 
	start date NOT NULL, 
	end date NOT NULL, 
	duration integer DEFAULT 30 NOT NULL, 
	startHour integer NOT NULL, 
	endHour integer NOT NULL, 
	fk_bookcal_calendar integer NOT NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;


SELECT * FROM llx_bookcal_availabilities
WHERE rowid = 1;
