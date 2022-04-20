-- ===================================================================
-- Copyright (C) 2004		Laurent Destailleur  <eldy@users.sourceforge.net>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
-- ===================================================================

CREATE TABLE llx_partnership(
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	ref varchar(128) DEFAULT '(PROV)' NOT NULL, 
	status smallint NOT NULL DEFAULT '0', 
	fk_type integer DEFAULT 0 NOT NULL,
	fk_soc integer, 
	fk_member integer, 
	date_partnership_start date NOT NULL, 
	date_partnership_end date NULL, 
	entity integer DEFAULT 1 NOT NULL,	-- multi company id, 0 = all
	reason_decline_or_cancel text NULL,
	date_creation datetime NOT NULL, 
	fk_user_creat integer NOT NULL, 
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
	fk_user_modif integer, 
	note_private text, 
	note_public text, 
	last_main_doc varchar(255), 
	count_last_url_check_error integer DEFAULT '0',
	last_check_backlink datetime NULL,
	import_key varchar(14),
	model_pdf varchar(255)
) ENGINE=innodb;