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
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
-- ========================================================================


CREATE TABLE llx_website
(
	rowid         integer AUTO_INCREMENT NOT NULL PRIMARY KEY,
	type_container varchar(16) NOT NULL DEFAULT 'page',
	entity        integer NOT NULL DEFAULT 1,
	ref	          varchar(128) NOT NULL,
	description   varchar(255),
	maincolor     varchar(16),
	maincolorbis  varchar(16),
	lang          varchar(8),
	otherlang     varchar(255),
	status		  integer DEFAULT 1,
	fk_default_home integer,
	use_manifest integer,
	virtualhost   varchar(255), 
    fk_user_creat integer,
    fk_user_modif integer,
    date_creation datetime,
    position      integer DEFAULT 0,
    lastaccess    datetime NULL,						-- updated at each page access
    lastpageid    integer DEFAULT 0,
    pageviews_previous_month BIGINT UNSIGNED DEFAULT 0,		
    pageviews_month BIGINT UNSIGNED DEFAULT 0,			-- increased by 1 at each page access, saved into pageviews_previous_month when on different month than lastaccess 
    pageviews_total BIGINT UNSIGNED DEFAULT 0,			-- increased by 1 at each page access, no reset
	tms           timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    import_key    varchar(14),      -- import key	
	name_template varchar(255) NULL   --name of template imported
) ENGINE=innodb;
