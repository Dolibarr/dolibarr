-- Copyright (C) 2016	Laurent Destailleur	<eldy@users.sourceforge.net>
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


CREATE TABLE llx_website_account(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	login             varchar(64) NOT NULL, 
    pass_encoding     varchar(24) NOT NULL,
    pass_crypted      varchar(128),
    pass_temp         varchar(128),			    -- temporary password when asked for forget password
    fk_soc integer,
	fk_website        integer NOT NULL,
	note_private      text,
    date_last_login   datetime,
    date_previous_login datetime,
	date_creation datetime NOT NULL, 
	tms timestamp NOT NULL, 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer, 
	import_key varchar(14), 
	status integer 
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;