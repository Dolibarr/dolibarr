-- Copyright (C) 2019 Laurent Destailleur  <eldy@users.sourceforge.net>
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


CREATE TABLE llx_mrp_mo(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	entity integer DEFAULT 1 NOT NULL, 
	ref varchar(128) DEFAULT '(PROV)' NOT NULL, 
	mrptype integer DEFAULT 0,                  -- 0 for a manufacture MO, 1 for a dismantle MO 
	label varchar(255), 
	qty real NOT NULL,
	fk_warehouse integer,
	fk_soc integer, 
	note_public text, 
	note_private text, 
	date_creation datetime NOT NULL, 
	date_valid datetime NULL,
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer,
	fk_user_valid integer,
	import_key varchar(14),
	model_pdf varchar(255),
	status integer NOT NULL, 
	fk_product integer NOT NULL, 
	date_start_planned datetime, 
	date_end_planned datetime, 
	fk_bom integer, 
	fk_project integer,
	last_main_doc varchar(255),
    fk_parent_line integer
    -- END MODULEBUILDER FIELDS
) ENGINE=innodb;
