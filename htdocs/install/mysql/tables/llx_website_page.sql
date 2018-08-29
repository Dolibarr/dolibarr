-- ========================================================================
-- Copyright (C) 2016	Laurent Destailleur	<eldy@users.sourceforge.net>
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
-- ========================================================================


CREATE TABLE llx_website_page
(
	rowid         integer AUTO_INCREMENT NOT NULL PRIMARY KEY,
	fk_website    integer NOT NULL,
	type_container varchar(16) NOT NULL DEFAULT 'page',
	pageurl       varchar(255) NOT NULL,
	title         varchar(255),						
	description   varchar(255),						
	keywords      varchar(255),
	lang          varchar(6),
	fk_page       integer,          
	htmlheader	  text,
	content		  mediumtext,		-- text is not enough in size
    status        integer DEFAULT 1,
	grabbed_from   varchar(255),
    fk_user_create integer,
    fk_user_modif  integer,
    date_creation  datetime,
	tms            timestamp,
    import_key     varchar(14)      -- import key
) ENGINE=innodb;
