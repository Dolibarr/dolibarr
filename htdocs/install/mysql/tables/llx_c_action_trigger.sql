-- ===================================================================
-- Copyright (C) 2011 Regis Houssin	<regis.houssin@capnetworks.com>
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
-- This table is a list of all available possible automatic actions
-- It is read only by admin page to select action code and save them 
-- into llx_const with name MAIN_AGENDA_ACTIONAUTO_XXX_YYY.
-- ===================================================================

create table llx_c_action_trigger
(
  rowid			integer AUTO_INCREMENT PRIMARY KEY,
  elementtype	varchar(24) 			NOT NULL,
  code			varchar(32)				NOT NULL,
  label			varchar(128)			NOT NULL,
  description	varchar(255),
  rang			integer		DEFAULT 0
)ENGINE=innodb;
