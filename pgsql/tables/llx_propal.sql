-- ===================================================================
-- Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2002-2003 Éric Seigne <erics@rycks.com>
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
-- ===================================================================

create table llx_propal
(
  rowid           SERIAL PRIMARY KEY,
  fk_soc          integer,
  fk_soc_contact  integer,
  fk_projet       integer default 0, -- projet auquel est rattache la propale
  ref             varchar(30) NOT NULL,  -- propal number
  datec           timestamp,              -- date de creation
  fin_validite    timestamp,              -- date de fin de validite
  date_valid      timestamp,              -- date de validation
  date_cloture    timestamp,              -- date de cloture
  datep           date,                  -- date de la propal
  fk_user_author  integer,   -- createur de la propale
  fk_user_valid   integer,   -- valideur de la propale
  fk_user_cloture integer,   -- cloture de la propale signee ou non signee
  fk_statut       smallint  default 0,
  price           real      default 0,
  remise_percent  real      default 0,
  remise          real      default 0,
  tva             real      default 0,
  total           real      default 0,
  note            text,
  model_pdf       varchar(50)
);

create unique index llx_propal_ref on llx_propal(ref);

