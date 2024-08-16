-- Copyright (C) 2021-2022 Laurent Destailleur  <eldy@users.sourceforge.net>
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


CREATE TABLE llx_eventorganization_conferenceorboothattendee(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	ref varchar(128) NOT NULL, 
	fk_soc integer,
	fk_actioncomm integer,
	fk_project integer NOT NULL,
	fk_invoice integer NULL,
	email varchar(128),
    email_company varchar(128),
	firstname varchar(100),
	lastname varchar(100),
	date_subscription datetime, 
	amount double DEFAULT NULL, 
	note_public text, 
	note_private text, 
	date_creation datetime NOT NULL, 
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
	fk_user_creat integer, 
	fk_user_modif integer, 
	last_main_doc varchar(255), 
	ip varchar(250),              --ip used to create record (for public submission page)
	import_key varchar(14), 
	model_pdf varchar(255), 
	status smallint NOT NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
