-- ===========================================================================
-- Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- ===========================================================================

create table llx_facture_rec
(
  rowid              int IDENTITY PRIMARY KEY,
  titre              varchar(50) NOT NULL,
  fk_soc             int NOT NULL,
  datec              datetime,  -- date de creation

  amount             real     DEFAULT 0 NOT NULL,
  remise             real     DEFAULT 0,
  remise_percent     real     DEFAULT 0,
  remise_absolue     real     DEFAULT 0,
  tva                real     DEFAULT 0,
  total              real     DEFAULT 0,
  total_ttc          real     DEFAULT 0,

  fk_user_author     int,             -- createur
  fk_projet          int,             -- projet auquel est associé la facture
  fk_cond_reglement  int DEFAULT 0,   -- condition de reglement
  fk_mode_reglement   int DEFAULT 0,  -- mode de reglement (Virement, Prélèvement)
  date_lim_reglement  datetime,               -- date limite de reglement

  note               text,
  note_public         text,

  frequency          char(2) DEFAULT NULL,
  last_gen           varchar(7) DEFAULT NULL
);

CREATE INDEX idx_facture_rec_fksoc ON llx_facture_rec(fk_soc)
