-- ===================================================================
-- Copyright (C) 2009 Laurent Destailleur <eldy@users.sourceforge.net>
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
--
-- List of tables for available import models
-- ===================================================================

create table llx_import_model
(
  	rowid         integer AUTO_INCREMENT PRIMARY KEY,
  	entity        integer DEFAULT 0 NOT NULL,      				-- by default on all entities for compatibility
	fk_user		  integer DEFAULT 0 NOT NULL,
  	label         varchar(50) NOT NULL,
  	type		  varchar(64) NOT NULL,
  	field         text NOT NULL
)ENGINE=innodb;
