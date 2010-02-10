-- ===========================================================================
-- Copyright (C) 2010 Regis Houssin  <regis@dolibarr.fr>
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
-- ===========================================================================

create table llx_projet_milestone
(
  rowid					integer AUTO_INCREMENT PRIMARY KEY,
  fk_projet				integer NOT NULL,
  label					varchar(255) NOT NULL,
  description			text,
  datec					datetime,						-- date creation
  tms					timestamp,						-- date creation/modification
  dateo					datetime,						-- date start milestone
  datee					datetime,						-- date end milestone
  priority				integer	DEFAULT 0,				-- priority
  fk_user_creat			integer,						-- user who created the task
  rang					integer	DEFAULT 0
)type=innodb;
