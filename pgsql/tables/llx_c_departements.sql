-- ========================================================================
-- Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
-- ========================================================================

create table llx_c_departements
(
  rowid       serial PRIMARY KEY,
  code_departement char(3),
  fk_region   integer,
  cheflieu    varchar(7),
  tncc        integer,
  ncc         varchar(50),
  nom         varchar(50),
  active      smallint default 1
);

CREATE INDEX llx_c_departements_fk_region ON llx_c_departements(fk_region);