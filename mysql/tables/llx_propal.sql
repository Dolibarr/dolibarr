-- ===================================================================
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
-- ===================================================================

create table llx_propal
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc          integer,
  fk_soc_contact  integer,
  fk_projet       integer default 0,     -- projet auquel est rattache la propale
  ref             varchar(30) NOT NULL,  -- propal number
  datec           datetime,              -- date de creation 
  date_valid      datetime,              -- date de validation
  date_cloture    datetime,              -- date de cloture
  datep           date,                  -- date de la propal
  fk_user_author  integer,               -- createur de la propale
  fk_user_valid   integer,               -- valideur de la propale
  fk_user_cloture integer,               -- cloture de la propale signee ou non signee
  fk_statut       smallint  default 0,
  price           real      default 0,
  remise          real      default 0,
  tva             real      default 0,
  total           real      default 0,
  note            text,

  UNIQUE INDEX (ref)
);
