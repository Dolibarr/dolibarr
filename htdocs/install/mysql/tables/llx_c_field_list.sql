-- ========================================================================
-- Copyright (C) 2010 Regis Houssin  <regis.houssin@capnetworks.com>
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
-- Change are done into list pages to use the new code to manage
-- selection by users of fields. Once all changes are done with new
-- code, we will be able to use this table to store the content of
-- the $arrayfields table.
-- Table not used / not required for the moment.
-- ========================================================================

create table llx_c_field_list
(
  rowid			integer  AUTO_INCREMENT PRIMARY KEY,
  tms			timestamp,
  element		varchar(64)        			NOT NULL,		-- name of element list
  entity		integer			DEFAULT 1 	NOT NULL,		-- entity id
  name			varchar(32)        			NOT NULL,		-- name of field with table alias (ex: p.ref)
  alias			varchar(32)					NOT NULL,		-- alias of field (ex: ref)
  title			varchar(32)        			NOT NULL,		-- title (translation) of field (ex: Ref)
  align			varchar(6)		DEFAULT 'left',				-- align (left,center,right)
  sort			tinyint 		DEFAULT 1  	NOT NULL,		-- add sort field
  search		tinyint 		DEFAULT 0  	NOT NULL,		-- add search field
  enabled       varchar(255)	DEFAULT 1,					-- Condition to show or hide
  rang      	integer 		DEFAULT 0
  
)ENGINE=innodb;
