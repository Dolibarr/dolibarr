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
	date_creation	datetime,			-- field included into line signature
	action varchar(50),				-- field included into line signature
	amounts double(24,8) NOT NULL,			-- field included into line signature
	vat double(24,8) NULL,			-- TODO
	ref_object varchar(255),			-- field included into line signature
	date_object	datetime,			-- field included into line signature
	user_fullname varchar(255),			-- field included into line signature
	object_data	mediumtext,			-- field included into line signature
	linktoref varchar(255),				-- TODO
	linktype varchar(16),				-- TODO
	-- the signature of line
	signature varchar(100) NOT NULL,  		-- the hash of the key for signature with previous hash before
	-- fields used for debug only or to retreive link to more complete business events
	element varchar(50),
	fk_user	integer,
	fk_object integer,
	object_version varchar(32) DEFAULT '',		-- in which version did the line was recorded
	certified integer,
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	debuginfo mediumtext
) ENGINE=innodb;
