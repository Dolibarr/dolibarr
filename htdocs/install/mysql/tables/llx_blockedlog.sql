-- ===================================================================
-- Copyright (C) 2007-2025 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	rowid integer AUTO_INCREMENT PRIMARY KEY,	-- Automatic sequence ID
	-- fields included into signature
	module_source varchar(32) DEFAULT '',		-- field included into line signature. If the event was recorded from a POS module or another module.
	pos_source varchar(32) DEFAULT '',			-- field included into line signature. The number of the terminal.
	action varchar(50),							-- field included into line signature. The type of event.
	entity integer DEFAULT 1 NOT NULL,			-- field included into line signature. For future usage of multi-entity.
	date_creation	datetime,					-- field included into line signature. Date and time of event.
	amounts double(24,8) NOT NULL,				-- field included into line signature (denormalized data from object_data)
	amounts_taxexcl double(24,8) NULL,			-- field included into line signature (denormalized data from object_data)
	ref_object varchar(255),					-- field included into line signature (denormalized data from object_data)
	date_object	datetime,						-- field included into line signature (denormalized data from object_data)
	user_fullname varchar(255),					-- field included into line signature. User recording the event.
	linktoref text,								-- field included into line signature. Link to another ref_object.
	linktype varchar(16),						-- field included into line signature. Link type.
	object_data	mediumtext,						-- field included into line signature
	-- the signature of line
	signature varchar(100) NOT NULL,  			-- the hash of the key for signature with previous hash before
	-- fields used for debug only
	element varchar(50),
	fk_user	integer,
	fk_object integer,
	object_version varchar(32) DEFAULT '',		-- in which version did the line was recorded
	object_format varchar(16) DEFAULT 'V1',     -- format of data stored in object_data
	certified integer,							-- not used, reserved for future use
	actionrefisunique varchar(16) DEFAULT NULL,	-- not used, reserved for future use
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	debuginfo mediumtext
) ENGINE=innodb;
