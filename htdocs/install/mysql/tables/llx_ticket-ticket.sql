-- SQL definition for module ticket
-- Copyright (C) 2013  Jean-François FERRY <hello@librethic.io>
-- Copyright (C) 2023  Charlene Benke <charlene@patas-monkey.com>
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
-- along with this program.  If not, see <https://www.gnu.org/licenses/>.

CREATE TABLE llx_ticket
(
	rowid       integer AUTO_INCREMENT PRIMARY KEY,
	entity		integer DEFAULT 1,
	ref			varchar(128) NOT NULL,
	track_id    varchar(128) NOT NULL,
	fk_soc		integer DEFAULT 0,
	fk_project	integer DEFAULT 0,
	fk_contract	integer DEFAULT 0,
	origin_email   varchar(128),
	origin_replyto   varchar(128),
	origin_references   text,
	fk_user_create	integer,
	fk_user_assign	integer,
	subject	varchar(255),
	message	mediumtext,
	fk_statut integer,
	resolution integer,
	progress integer DEFAULT 0,				-- progression 0 - 100 or null
	duration integer,				        -- duration (Sum of linked fichinter) or null
	timing varchar(20),
	type_code varchar(32),
	category_code varchar(32),
	severity_code varchar(32),
	datec datetime,							-- date of creation of record
	date_read datetime,
	date_last_msg_sent datetime,
	date_close datetime,
	notify_tiers_at_create tinyint,
	email_msgid varchar(255),				-- if ticket is created by email collector, we store here MSG ID
	email_date datetime,					-- if ticket is created by email collector, we store here Date of message
	ip varchar(250),
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	model_pdf varchar(255),
	last_main_doc varchar(255),				-- relative filepath+filename of last main generated document
	extraparams varchar(255),				-- to save other parameters with json format
	barcode varchar(255) DEFAULT NULL,          -- barcode
    fk_barcode_type integer DEFAULT NULL,          -- barcode type
    import_key        varchar(14)
)ENGINE=innodb;
