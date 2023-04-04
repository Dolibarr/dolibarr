-- ========================================================================
-- Copyright (C) 2020 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- This table can be used for user defined session management
-- ========================================================================

create table llx_session
(
  session_id varchar(50) PRIMARY KEY,
  session_variable text,
  last_accessed datetime NOT NULL,
  fk_user integer NOT NULL,
  remote_ip varchar(64) NULL,
  user_agent varchar(255) NULL
)ENGINE=innodb;
