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
create table llx_telephonie_contrat (
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  ref                varchar(25) NOT NULL,

  fk_client_comm     integer NOT NULL,      -- Client décideur
  fk_soc             integer NOT NULL,      -- Client réel (installation, agence)
  fk_soc_facture     integer NOT NULL,      -- Client facturé

  statut             smallint DEFAULT 0,

  fk_commercial_sign integer NOT NULL,
  fk_commercial_suiv integer NOT NULL,

  fk_user_creat      integer,
  date_creat         datetime,

  isfacturable       enum('oui','non') DEFAULT 'oui',
  mode_paiement      enum('vir','pre') DEFAULT 'pre',

  grille_tarif       integer DEFAULT 1,

  note               text,

  UNIQUE (ref)

)type=innodb;


