-- ============================================================================
-- Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2002-2003 Éric Seigne <erics@rycks.com>
-- Copyright (C) 2004 Benoit Mortier <benoit.mortier@opensides.be>
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
  rowid         SERIAL PRIMARY KEY,
  datec         timestamp,
  tms           timestamp,
  login         varchar(8),
  pass          varchar(32),
  name          varchar(50),
  firstname     varchar(50),
  code          varchar(4),
  email         varchar(255),
  admin         smallint default 0,
  webcal_login   varchar(25),
  module_comm   smallint default 1,
  module_compta smallint default 1,
  fk_societe    integer default 0,
  fk_socpeople  integer default 0,
  note          text
);

create unique index llx_user_login on llx_user(login);

