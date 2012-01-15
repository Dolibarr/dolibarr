-- ========================================================================
-- Copyright (C) 2007      Patrick Raguin      <patrick.raguin@gmail.com>
-- Copyright (C) 2007-2008 Laurent Destailleur <eldy@users.sourceforge.net>
-- Copyright (C) 2009      Regis Houssin       <regis@dolibarr.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
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


CREATE TABLE llx_menu
(
	rowid         integer AUTO_INCREMENT NOT NULL PRIMARY KEY,
	menu_handler  varchar(16) NOT NULL,             -- Menu handler name
	entity        integer DEFAULT 1 NOT NULL,       -- Multi company id
	module        varchar(64),                      -- Module name if record is added by a module
	type          varchar(4) NOT NULL,              -- Menu top or left
	mainmenu      varchar(100) NOT NULL,            -- Name family/module for top menu (home, companies, ...)
    leftmenu      varchar(100) NULL,                -- Name family/module for left menu (setup, info, ...)
	fk_menu       integer NOT NULL,                 -- 0 or Id of mother menu line, or -1 if we use fk_mainmenu and fk_leftmenu
    fk_mainmenu   varchar(16),                      -- 
    fk_leftmenu   varchar(16),                      -- 
	position      integer NOT NULL,                 -- Sort order of entry
	url           varchar(255) NOT NULL,            -- Relative (or absolute) url to go
	target        varchar(100) NULL,                -- Target of Url link
	titre         varchar(255) NOT NULL,            -- Key for menu translation 
	langs         varchar(100),                     -- Lang file to load for translation
	level         smallint,                         -- Deprecated. Not used.
	perms         varchar(255),                     -- Condition to show enabled or disabled
	enabled       varchar(255) NULL default '1',    -- Condition to show or hide
	usertype      integer NOT NULL default '0',     -- 0 if menu for all users, 1 for external only, 2 for internal only
	tms           timestamp
) ENGINE=innodb;

-- 
-- List of codes for the field entity
--
-- 1 : first company product
-- 2 : second company product
-- 3 : etc...
--