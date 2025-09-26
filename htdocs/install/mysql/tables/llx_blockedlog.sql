-- ===================================================================
-- Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- ===================================================================

CREATE TABLE llx_blockedlog
(
	rowid integer AUTO_INCREMENT PRIMARY KEY,
	entity integer DEFAULT 1 NOT NULL,
	-- fields included into signature
	date_creation	datetime,
	action varchar(50),
	amounts double(24,8) NOT NULL,
	ref_object varchar(255),
	date_object	datetime,
	user_fullname varchar(255),
	object_data	mediumtext,
	-- the signature of line
	signature varchar(100) NOT NULL,  			-- the hash of the key for signature with previous hash before
	-- fields used for debug only
	element varchar(50),
	fk_user	integer,
	fk_object integer,
	signature_line varchar(100) NOT NULL, 		-- the hash of the key for signature for line only so without previous hash before (not used)
	object_version varchar(32) DEFAULT '',		-- in which version did the line was recorded
	certified integer,
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	debuginfo mediumtext
) ENGINE=innodb;
