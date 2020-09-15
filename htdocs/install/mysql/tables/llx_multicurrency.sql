-- ========================================================================
-- Copyright (C) 2016		Pierre-Henry Favre		<phf@atm-consulting.fr>
-- Copyright (C) 2016       Laurent Destailleur     <eldy@users.sourceforge.net>
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
-- ========================================================================

CREATE TABLE llx_multicurrency
( 
	rowid integer AUTO_INCREMENT PRIMARY KEY, 
	date_create datetime DEFAULT NULL, 
	code varchar(255) DEFAULT NULL, 
	name varchar(255) DEFAULT NULL, 
	entity integer DEFAULT 1,
	fk_user integer DEFAULT NULL
) ENGINE=innodb;
