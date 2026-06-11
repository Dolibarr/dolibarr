-- ========================================================================
-- Copyright (C) 2026 Braito <braito4@hotmail.com>
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
-- Table for AI processing metadata linked to agenda events.
-- ========================================================================

CREATE TABLE llx_actioncomm_ai
(
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	entity integer DEFAULT 1 NOT NULL,
	fk_actioncomm integer NOT NULL,

	operation_code varchar(64) NOT NULL,
	operation_version varchar(32),

	provider varchar(64),
	model varchar(190),

	prompt_code varchar(128),
	prompt_version varchar(32),
	prompt_hash varchar(80),

	input_hash varchar(80),
	output_hash varchar(80),
	security_hash varchar(128),

	confidence double,
	status varchar(32),

	privacy_profile_code varchar(128),
	privacy_profile_version varchar(32),
	pii_redaction_enabled smallint DEFAULT 0,

	input_metadata_json LONGTEXT,
	output_json LONGTEXT,
	error_message varchar(255),

	fk_user_creat integer,
	date_creation datetime,
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=innodb;
