-- ============================================================================
-- Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
-- $Source$
--
-- ===========================================================================

create table llx_user
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,
  datec         datetime,
  tms           timestamp,
  login         varchar(8),
  pass          varchar(32),
  name          varchar(50),
  firstname     varchar(50),
  code          varchar(4),
  email         varchar(255),
  admin         smallint DEFAULT 0,
  webcal_login  varchar(25),
  module_comm   smallint DEFAULT 1,
  module_compta smallint DEFAULT 1,
  fk_societe    integer DEFAULT 0,
  fk_socpeople  integer DEFAULT 0,
  note          text,

  UNIQUE INDEX(login)
)type=innodb;
