-- ============================================================================
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
-- ============================================================================

create table llx_contratdet
(
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  tms                   timestamp,

  fk_contrat            integer NOT NULL,
  fk_product            integer NOT NULL,

  statut                smallint DEFAULT 0,

  label                 text, -- libellé du produit
  description           text,

  date_commande         datetime,
  date_ouverture_prevue datetime,
  date_ouverture        datetime, -- date d'ouverture du service chez le client
  date_fin_validite     datetime,
  date_cloture          datetime,

  tva_tx                real DEFAULT 19.6, -- taux tva
  qty                   real,              -- quantité
  remise_percent        real DEFAULT 0,    -- pourcentage de remise
  remise                real DEFAULT 0,    -- montant de la remise
  subprice              real,              -- prix avant remise
  price_ht              real,              -- prix final

  fk_user_author        integer NOT NULL default 0,
  fk_user_ouverture     integer,
  fk_user_cloture       integer,
  commentaire           text

)type=innodb;
--
--
ALTER TABLE llx_contratdet ADD INDEX (fk_contrat);
ALTER TABLE llx_contratdet ADD INDEX (fk_product);
--
--
ALTER TABLE llx_contratdet ADD FOREIGN KEY (fk_contrat) REFERENCES llx_contrat (rowid);
ALTER TABLE llx_contratdet ADD FOREIGN KEY (fk_product) REFERENCES llx_product (rowid);
