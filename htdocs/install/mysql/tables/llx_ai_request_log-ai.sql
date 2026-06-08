-- ===================================================================
-- Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2008 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- ===================================================================


create table llx_ai_request_log
(
  rowid						integer AUTO_INCREMENT PRIMARY KEY,
  entity					integer DEFAULT 1 NOT NULL,
  date_request				datetime,
  fk_user					integer NOT NULL,						-- ID of user
  query_text				text,									-- User prompt
  tool_name					varchar(255),							-- Tool used
  provider					varchar(50),							-- LLM provider
  execution_time			float,									-- Execution time
  confidence				float,									-- LLM confidence
  status					varchar(50),							-- Response status
  error_msg					text,									-- Error message
  raw_request_payload		MEDIUMTEXT,								-- Request payload
  raw_response_payload		MEDIUMTEXT								-- Response payload
)ENGINE=innodb;
