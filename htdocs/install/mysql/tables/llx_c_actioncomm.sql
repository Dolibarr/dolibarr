-- ========================================================================
-- Copyright (C) 2001-2002,2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2004-2010      Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- $Id: llx_c_actioncomm.sql,v 1.5 2011/08/03 01:25:39 eldy Exp $
-- ========================================================================

create table llx_c_actioncomm
(
  id         integer     PRIMARY KEY,
  code       varchar(12) UNIQUE NOT NULL,
  type       varchar(10) DEFAULT 'system' NOT NULL,
  libelle    varchar(48) NOT NULL,
  module	 varchar(16) DEFAULT NULL,
  active     tinyint DEFAULT 1 NOT NULL,
  todo       tinyint,
  position   integer NOT NULL DEFAULT 0
)ENGINE=innodb;
