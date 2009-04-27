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
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- $Id$
-- ========================================================================


CREATE TABLE llx_menu
(
	rowid         integer AUTO_INCREMENT NOT NULL PRIMARY KEY,
	menu_handler  varchar(16) NOT NULL,             -- Menu handler name
	entity        integer DEFAULT 1 NOT NULL,       -- Multi company id
	module        varchar(64),                      -- Module name if record is added by a module
	type          varchar(4) NOT NULL,              -- Menu top or left
	mainmenu      varchar(100) NOT NULL,            -- Name family/module (home, companies, ...)
	fk_menu       int(11) NOT NULL,                 -- 0 or Id of mother menu line
	position      integer NOT NULL,                 -- Sort order of entry
	url           varchar(255) NOT NULL,            -- Relative (or absolute) url to go
	target        varchar(100) NULL,                -- Target of Url link
	titre         varchar(255) NOT NULL,            -- Key for menu translation 
	langs         varchar(100),                     -- Lang file to load for translation
	level         tinyint(1),                       -- Used by auguria menu only. Do not use.
	leftmenu      varchar(1) NULL default '1',      -- Say if left menu defined in pre.inc.php and used by top menu must be overwritten by dynamic databse menu (1=yes by default)
	perms         varchar(255),                     -- Condition to show enabled or disabled
	enabled       varchar(255) NULL default '1',    -- Condition to show or hide
	user          integer NOT NULL default '0',     -- 0 if menu for all users, 1 for external only, 2 for internal only
	tms           timestamp
) type=innodb;

-- 
-- List of codes for the field entity
--
-- 1 : first company product
-- 2 : second company product
-- 3 : etc...
--