-- ===================================================================
-- Copyright (C) 2026	Jose Martinez			<jose.martinez@pichinov.com>
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

create table llx_ai_chat_message
(
  rowid						integer AUTO_INCREMENT PRIMARY KEY,
  fk_conversation			integer NOT NULL,
  role						varchar(16) NOT NULL,					-- user | assistant
  content_raw				text,									-- Plain text (what pinning sends to the model)
  content_html				MEDIUMTEXT,								-- Rendered bubble, sanitized server-side at save time
  tool_name					varchar(255),
  pinned					smallint DEFAULT 0,						-- Context pin state, restored on reopen
  is_error					smallint DEFAULT 0,						-- Provider failure: never sent back as context by default
  position					integer DEFAULT 0,
  datec						datetime NOT NULL
)ENGINE=innodb;
