-- ========================================================================
-- Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
-- ========================================================================
--
--
-- tlink lien vers un tarif, permets la mise à jour par exemple de tous les tarifs
-- découpés par pays.

create table llx_telephonie_tarif (
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  libelle     varchar(255),
  type        enum ('INT','NAT','MOB'),
  tlink        integer DEFAULT 0,

  UNIQUE INDEX(libelle)
)type=innodb;

