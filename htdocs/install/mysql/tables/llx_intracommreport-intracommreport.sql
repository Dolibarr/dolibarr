-- ===================================================================
-- Copyright (C) 2015		ATM Consulting					<support@atm-consulting.fr>
-- Copyright (C) 2019		Open-DSI						<support@open-dsi.fr>
-- Copyright (C) 2024		Francis Appels					<francis.appels@z-application.com>
-- Copyright (C) 2025		Alexandre Spangaro				<alexandre@inovea-conseil.com>
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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ===================================================================

CREATE TABLE llx_intracommreport (
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	entity integer DEFAULT 1 NOT NULL,
	ref varchar(128) DEFAULT '(PROV)' NOT NULL,
	label varchar(255),
	exporttype varchar(64) DEFAULT 'deb' NOT NULL,
	type_declaration varchar(64) DEFAULT 'expedition' NOT NULL,
	period_month integer,
	period_year integer,
	amount double DEFAULT NULL,
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
	status integer NOT NULL
)ENGINE=innodb;
