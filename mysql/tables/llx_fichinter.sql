-- ===================================================================
-- Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
-- ===================================================================

create table llx_fichinter
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc          integer NOT NULL,
  fk_projet       integer default 0,     -- projet auquel est rattache la fiche
  ref             varchar(30) NOT NULL,  -- number

  datec           datetime,              -- date de creation 
  date_valid      datetime,              -- date de validation

  datei           date,                  -- date de l'intervention

  fk_user_author  integer,   -- createur de la fiche

  fk_user_valid   integer,   -- valideur de la fiche

  fk_statut       smallint  default 0,

  duree           real,

  note            text,

  UNIQUE INDEX (ref)
);
