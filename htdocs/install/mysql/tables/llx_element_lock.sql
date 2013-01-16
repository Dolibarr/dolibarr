-- ============================================================================
-- Copyright (C) 2011 Regis Houssin <regis.houssin@capnetworks.com>
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
-- ============================================================================

create table llx_element_lock
(
	rowid				integer	AUTO_INCREMENT	PRIMARY KEY,
	fk_element			integer NOT NULL,
	elementtype			varchar(32) NOT NULL,
	datel    			datetime,				-- date of lock
	datem    			datetime,				-- date of unlock/modif
	sessionid			varchar(255)	
)ENGINE=innodb;
