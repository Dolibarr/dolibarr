-- ===========================================================================
-- Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General [public] License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General [public] License for more details.
--
-- You should have received a copy of the GNU General [public] License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- $Id$
-- $Source$
-- 
-- ===========================================================================

create table llx_facture_fourn
(
  rowid      int IDENTITY PRIMARY KEY,
  facnumber  varchar(50) NOT NULL,
  type		 smallint DEFAULT 0 NOT NULL,
  fk_soc     int NOT NULL,
  datec      datetime,    -- date de creation de la facture
  datef      datetime,        -- date de la facture
  libelle    varchar(255),
  paye       smallint DEFAULT 0 NOT NULL,
  amount     real     DEFAULT 0 NOT NULL,
  remise     real     DEFAULT 0,
  tva        real     DEFAULT 0,
  total      real     DEFAULT 0,
  total_ht   real     DEFAULT 0,
  total_tva  real     DEFAULT 0,
  total_ttc  real     DEFAULT 0,

  fk_statut  smallint DEFAULT 0 NOT NULL,

  fk_user_author  int,   -- createur de la facture
  fk_user_valid   int,   -- valideur de la facture

  fk_projet           int,   -- projet auquel est associée la facture

  fk_cond_reglement   int  DEFAULT 1 NOT NULL,   -- condition de reglement (30 jours, fin de mois ...)
  date_lim_reglement  datetime,      -- date limite de reglement

  note       	text,
  note_public	text
);
