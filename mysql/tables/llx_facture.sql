-- ===========================================================================
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
-- ===========================================================================

create table llx_facture
(
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  facnumber          varchar(50)        NOT NULL,
  fk_soc             integer            NOT NULL,
  datec              datetime,  -- date de creation de la facture
  datef              date,      -- date de la facture
  paye               smallint DEFAULT 0 NOT NULL,
  amount             real     DEFAULT 0 NOT NULL,
  remise             real     DEFAULT 0,
  remise_percent     real     DEFAULT 0,
  tva                real     DEFAULT 0,
  total              real     DEFAULT 0,
  total_ttc          real     DEFAULT 0,
  fk_statut          smallint DEFAULT 0 NOT NULL,
  author             varchar(50),
  fk_user            integer,   -- createur de la facture
  fk_user_author     integer,   -- createur de la propale
  fk_user_valid      integer,   -- valideur de la propale
  fk_projet          integer,   -- projet auquel est associé la facture
  fk_cond_reglement  integer,   -- condition de reglement
  date_lim_reglement date,      -- date limite de reglement
  note               text,

  UNIQUE INDEX (facnumber),
  INDEX fksoc (fk_soc)
)type=innodb;
