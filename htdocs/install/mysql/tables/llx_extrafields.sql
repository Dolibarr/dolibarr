-- ===================================================================
-- Copyright (C) 2011-2012 Regis Houssin        <regis.houssin@capnetworks.com>
-- Copyright (C) 2011-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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

create table llx_extrafields
(
	rowid           integer AUTO_INCREMENT PRIMARY KEY,
	name            varchar(64) NOT NULL,         				-- name of field into extrafields tables
	entity          integer DEFAULT 1 NOT NULL,				-- multi company id
    elementtype     varchar(64) NOT NULL DEFAULT 'member',	-- for which element this extra fields is for
	tms             timestamp,									-- date of last update
	label           varchar(255) NOT NULL,        				-- label to show for attribute
	type            varchar(8),
	size            varchar(8) DEFAULT NULL,
	fieldunique     integer DEFAULT 0,
	fieldrequired   integer DEFAULT 0,
	perms			varchar(255),								-- not used yet
	pos             integer DEFAULT 0,
	alwayseditable  integer DEFAULT 0,							-- 1 if field can be edited whatever is element status
	param			text,										-- extra parameters to define possible values of field
	list			integer DEFAULT 0,							-- list of values for field that are combo lists
	ishidden		integer DEFAULT 0							-- ??? example of use case ???
)ENGINE=innodb;
