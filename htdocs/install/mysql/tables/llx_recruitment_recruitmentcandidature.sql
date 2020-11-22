-- Copyright (C) 2020	Laurent Destailleur	<eldy@users.sourceforge.net>
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


CREATE TABLE llx_recruitment_recruitmentcandidature(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	entity integer NOT NULL DEFAULT 1,
	ref varchar(128) DEFAULT '(PROV)' NOT NULL,
	fk_recruitmentjobposition INTEGER NULL,
	description text, 
	note_public text, 
	note_private text, 
	date_creation datetime NOT NULL,
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer, 
	import_key varchar(14), 
	model_pdf varchar(255), 
	status smallint NOT NULL, 
	firstname varchar(128), 
	lastname varchar(128), 
	email varchar(255),
	phone varchar(64), 
	date_birth date,
	remuneration_requested integer, 
	remuneration_proposed integer,
	email_msgid varchar(255),
	fk_recruitment_origin INTEGER NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
