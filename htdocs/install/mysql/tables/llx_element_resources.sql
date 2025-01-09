--
-- Copyright (C) 2013	Jean-François Ferry	<jfefe@aternatik.fr>
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
-- ============================================================================
-- Table used to link an element actioncomm with a resource or user (llx_resource or llx_user)
-- ============================================================================

CREATE TABLE llx_element_resources
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  element_id	  integer,
  element_type    varchar(64),
  resource_id     integer,			-- id of resource or id of user
  resource_type	  varchar(64),		-- resource or user
  busy			  integer,
  mandatory		  integer,
  duree				real,               -- total duration of using ressource
  fk_user_create  integer,
  tms             timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)ENGINE=innodb;
