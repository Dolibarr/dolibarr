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
-- along with this program.  If not, see https://www.gnu.org/licenses/.
--
-- Table to store accounts of thirdparties on external websites (like on stripe field site = 'stripe')
-- or on local website (fk_website).

CREATE TABLE llx_societe_account(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	entity	integer DEFAULT 1, 
	login             varchar(128) NOT NULL, 
	pass_encoding     varchar(24),
	pass_crypted      varchar(128),
	pass_temp         varchar(128),			    -- temporary password when asked for forget password
	fk_soc integer,
	fk_website        integer,					-- id of local web site
	site              varchar(128),				-- name of external web site
	site_account      varchar(128),				-- a key to identify the account on external web site
	key_account       varchar(128),				-- the id of third party in external web site (for site_account if site_account defined)
	note_private      text,
	date_last_login   datetime,
	date_previous_login datetime,
	date_creation datetime NOT NULL, 
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer, 
	import_key varchar(14), 
	status integer 
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
