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


CREATE TABLE llx_bom_bom(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	entity integer DEFAULT 1 NOT NULL,
	ref varchar(128) NOT NULL,
	bomtype integer DEFAULT 0,                  -- 0 for a BOM to manufacture, 1 for a BOM to dismantle
	label varchar(255),
	fk_product integer,
	description text,
	note_public text,
	note_private text,
	fk_warehouse integer,
	qty double(24,8),
	efficiency double(24,8) DEFAULT 1,
	duration double(24,8) DEFAULT NULL,
	date_creation datetime NOT NULL,
	date_valid datetime,
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_creat integer NOT NULL,
	fk_user_modif integer,
	fk_user_valid integer,
	import_key varchar(14),
	model_pdf varchar(255),
	status integer NOT NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
