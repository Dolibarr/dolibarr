-- Copyright (C) 2021 Gauthier VERDOL <gauthier.verdol@atm-consulting.fr>
-- Copyright (C) 2021 Greg Rastklan <greg.rastklan@atm-consulting.fr>
-- Copyright (C) 2021 Jean-Pascal BOUDET <jean-pascal.boudet@atm-consulting.fr>
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


CREATE TABLE llx_hrm_evaluation(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	entity INTEGER DEFAULT 1 NOT NULL,     -- multi company id
	ref varchar(128) DEFAULT '(PROV)' NOT NULL,
	label varchar(255),
	description text,
	note_public text,
	note_private text,
	model_pdf varchar(255),
	last_main_doc varchar(255),
	date_creation datetime NOT NULL,
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_creat integer NOT NULL,
	fk_user_modif integer,
	import_key varchar(14),
	status smallint NOT NULL,
	date_eval date,
	fk_user integer NOT NULL,
	fk_job integer NOT NULL
    -- END MODULEBUILDER FIELDS
) ENGINE=innodb;
