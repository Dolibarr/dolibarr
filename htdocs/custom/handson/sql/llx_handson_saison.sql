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


CREATE TABLE llx_handson_saison(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	ref varchar(128) NOT NULL, 
	name varchar(255) NOT NULL, 
	type integer NOT NULL, 
	season_start int, 
	season_end int, 
	register_start int, 
	register_end int, 
	edit_end int, 
	assignment_start int, 
	players_age_min int, 
	players_age_max int, 
	players_count_min int, 
	players_count_max int, 
	date_creation datetime NOT NULL, 
	tms timestamp, 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
