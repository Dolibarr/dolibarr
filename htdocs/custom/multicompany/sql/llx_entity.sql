-- ============================================================================
-- Copyright (C) 2010-2019 Regis Houssin  <regis.houssin@inodbox.com>
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
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- ===========================================================================

create table llx_entity
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  label				varchar(255) NOT NULL,
  description		text,
  tms				timestamp,
  datec				datetime,
  fk_user_creat		integer,
  options			text,
  visible			tinyint DEFAULT 1 NOT NULL,		-- 0 not visible, 1 visible, 2 template
  active			tinyint DEFAULT 1 NOT NULL,
  rang				smallint DEFAULT 0 NOT NULL
  
) ENGINE=innodb;