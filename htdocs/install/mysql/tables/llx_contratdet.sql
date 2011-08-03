-- ============================================================================
-- Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2010 Juanjo Menent        <jmenent@2byte.es>
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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- $Id: llx_contratdet.sql,v 1.5 2011/08/03 01:25:32 eldy Exp $
-- ============================================================================

create table llx_contratdet
(
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  tms                   timestamp,

  fk_contrat            integer       NOT NULL,
  fk_product            integer       NULL,                -- doit pouvoir etre nul pour ligne detail sans produits

  statut                smallint      DEFAULT 0,

  label                 text,                              -- libelle du produit
  description           text,
  fk_remise_except		  integer       NULL,                -- Lien vers table des remises fixes

  date_commande         datetime,
  date_ouverture_prevue datetime,
  date_ouverture        datetime,                          -- date d'ouverture du service chez le client
  date_fin_validite     datetime,
  date_cloture          datetime,

  tva_tx                double(6,3)   DEFAULT 0, 	         -- taux tva
  localtax1_tx		double(6,3)   DEFAULT 0,           -- local tax 1 rate
  localtax2_tx		double(6,3)   DEFAULT 0,           -- local tax 2 rate
  qty                   real          NOT NULL,            -- quantity
  remise_percent        real          DEFAULT 0,    		   -- pourcentage de remise
  subprice              double(24,8)  DEFAULT 0,           -- prix unitaire
  price_ht              real,              		             -- prix final (obsolete)
  remise                real          DEFAULT 0,    		             -- montant de la remise (obsolete)
  total_ht              double(24,8)  DEFAULT 0,     		   -- Total HT de la ligne toute quantite et incluant remise ligne et globale
  total_tva             double(24,8)  DEFAULT 0,	   		   -- Total TVA de la ligne toute quantite et incluant remise ligne et globale
  total_localtax1       double(24,8)  DEFAULT 0,	   		   -- Total Local tax 1 de la ligne
  total_localtax2       double(24,8)  DEFAULT 0,	   		   -- Total Local tax 2 de la ligne
  total_ttc             double(24,8)  DEFAULT 0,	   		   -- Total TTC de la ligne toute quantite et incluant remise ligne et globale
  info_bits		          integer       DEFAULT 0, 		       -- TVA NPR ou non

  fk_user_author        integer       NOT NULL DEFAULT 0,
  fk_user_ouverture     integer,
  fk_user_cloture       integer,
  commentaire           text

)ENGINE=innodb;
