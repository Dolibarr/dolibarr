-- ========================================================================
-- Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
-- Statut des lignes
--
-- 0 a commander
-- 1 commandée
-- 2 recue
-- 3 probleme
--
create table llx_telephonie_societe_ligne (
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  datec              datetime,
  fk_contrat         integer,
  fk_client_comm     integer NOT NULL,      -- Client décideur
  fk_soc             integer NOT NULL,
  ligne              varchar(12) NOT NULL,
  fk_soc_facture     integer NOT NULL,
  statut             smallint DEFAULT 0,
  fk_fournisseur     integer NOT NULL,
  remise             real DEFAULT 0,
  note               text,
  fk_commercial      integer NOT NULL,
  fk_commercial_sign integer NOT NULL,
  fk_commercial_suiv integer NOT NULL,
  fk_concurrent      integer DEFAULT 1 NOT NULL,
  fk_user_creat      integer,
  date_commande      datetime,
  fk_user_commande   integer,
  isfacturable       enum('oui','non') DEFAULT 'oui',
  mode_paiement      enum('vir','pre') DEFAULT 'pre',

  code_analytique    varchar(12),

  pdfdetail          varchar(50) DEFAULT 'standard' NOT NULL,

  UNIQUE INDEX(fk_soc, ligne)
)type=innodb;


