-- ===================================================================
-- Copyright (C) 2016	   Alexandre Spangaro  <aspangaro.dolibarr@gmail.com>
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

create table llx_note
(
	rowid			integer AUTO_INCREMENT PRIMARY KEY,
	entity			integer   DEFAULT 1 NOT NULL,			-- Multi company id
	datec			datetime,
	tms				timestamp,
	objecttype		varchar(24) NOT NULL,					-- Example : Salary / Product / Contact
	objectid		integer,								-- Id of the object
	type			tinyint DEFAULT 0,						-- 0 : private note | 1 : public note
	title			varchar(255),
	text			text DEFAULT NULL,
	fk_user_author	integer DEFAULT NULL,
	fk_user_modif	integer DEFAULT NULL
)ENGINE=innodb;