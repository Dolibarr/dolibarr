-- ============================================================================
-- Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2002-2003 ?ric Seigne <erics@rycks.com>
-- Copyright (C) 2004 Benoit Mortier <benoit.mortier@opensides.be>
--
-- $Id$
-- $Source$
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
-- ============================================================================

create table llx_socpeople
(
  idp         SERIAL PRIMARY KEY,
  datec       timestamp,
  fk_soc      integer,
  name        varchar(50),
  firstname   varchar(50),
  address     varchar(255),
  poste       varchar(80),
  phone       varchar(30),
  fax         varchar(30),
  email       varchar(255),
  fk_user     integer default 0,
  note        text
);

CREATE INDEX llx_socpeople_fk_soc ON llx_socpeople(fk_soc);
