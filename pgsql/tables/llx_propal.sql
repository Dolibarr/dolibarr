-- ===================================================================
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
-- ===================================================================

create table llx_propal
(
  rowid           SERIAL PRIMARY KEY,
  fk_soc          integer,
  fk_soc_contact  integer,
  fk_projet       integer DEFAULT 0,              -- projet auquel est rattache la propale
  ref             varchar(30) NOT NULL,           -- propal number
  datec           timestamp without time zone,    -- date de creation
  fin_validite    timestamp without time zone,    -- date de fin de validite
  date_valid      timestamp without time zone,    -- date de validation
  date_cloture    timestamp without time zone,    -- date de cloture
  datep           date,                           -- date de la propal
  fk_user_author  integer,                        -- createur de la propale
  fk_user_valid   integer,                        -- valideur de la propale
  fk_user_cloture integer,                        -- cloture de la propale signee ou non signee
  fk_statut       smallint  DEFAULT 0,
  price           real      DEFAULT 0,
  remise_percent  real      DEFAULT 0,
  remise          real      DEFAULT 0,
  tva             real      DEFAULT 0,
  total           real      DEFAULT 0,
  note            text,
  model_pdf       varchar(50)
);

create unique index llx_propal_ref on llx_propal(ref);

