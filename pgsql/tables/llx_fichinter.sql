-- ===================================================================
-- Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2002-2003 Éric Seigne <erics@rycks.com>
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
  rowid           SERIAL PRIMARY KEY,
  fk_soc          integer NOT NULL,
  fk_projet       integer default 0,     -- projet auquel est rattache la fiche
  ref             varchar(30) NOT NULL,  -- number
  datec           timestamp,              -- date de creation
  date_valid      timestamp,              -- date de validation
  datei           date,                  -- date de l'intervention
  fk_user_author  integer,   -- createur de la fiche
  fk_user_valid   integer,   -- valideur de la fiche
  fk_statut       smallint  default 0,
  duree           real,
  note            text
);

CREATE UNIQUE INDEX llx_fichinter_ref ON llx_fichinter(ref);

CREATE INDEX llx_fichinter_fk_soc ON llx_fichinter(fk_soc);
