-- ============================================================================
-- Copyright (C) 2004		Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2010-2013	Juanjo Menent        <jmenent@2byte.es>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
-- ============================================================================

create table llx_contratdet
(
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  tms                   timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  fk_contrat            integer       NOT NULL,
  fk_product            integer       NULL,                -- doit pouvoir etre nul pour ligne detail sans produits

  statut                smallint      DEFAULT 0,

  label                 text,                              -- libelle du produit
  description           text,
  fk_remise_except		integer       NULL,                -- Lien vers table des remises fixes

  date_commande         datetime,
  date_ouverture_prevue datetime,
  date_ouverture        datetime,                          -- date d'ouverture du service chez le client
  date_fin_validite     datetime,
  date_cloture          datetime,

  vat_src_code			varchar(10)   DEFAULT '',		   -- Vat code used as source of vat fields. Not strict foreign key here.
  tva_tx                double(6,3)   DEFAULT 0, 	       -- taux tva
  localtax1_tx		    double(6,3)   DEFAULT 0,           -- local tax 1 rate
  localtax1_type		varchar(10)	  	 NULL, 		       -- localtax1 type
  localtax2_tx		    double(6,3)   DEFAULT 0,           -- local tax 2 rate
  localtax2_type		varchar(10)	  	 NULL, 			   -- localtax2 type
  qty                   real          NOT NULL,            -- quantity
  remise_percent        real          DEFAULT 0,    	   -- pourcentage de remise
  subprice              double(24,8)  DEFAULT 0,           -- prix unitaire
  price_ht              real,              		           -- prix final (obsolete)
  remise                real          DEFAULT 0,    		             -- montant de la remise (obsolete)
  total_ht              double(24,8)  DEFAULT 0,     		   -- Total HT de la ligne toute quantite et incluant remise ligne et globale
  total_tva             double(24,8)  DEFAULT 0,	   		   -- Total TVA de la ligne toute quantite et incluant remise ligne et globale
  total_localtax1       double(24,8)  DEFAULT 0,	   		   -- Total Local tax 1 de la ligne
  total_localtax2       double(24,8)  DEFAULT 0,	   		   -- Total Local tax 2 de la ligne
  total_ttc             double(24,8)  DEFAULT 0,	   		   -- Total TTC de la ligne toute quantite et incluant remise ligne et globale
  product_type			integer       DEFAULT 1,               -- Type of line (1=service by default)
  info_bits		        integer DEFAULT 0, 		               -- TVA NPR ou non

  buy_price_ht          double(24,8)  DEFAULT NULL,            -- buying price
  fk_product_fournisseur_price integer DEFAULT NULL,           -- reference of supplier price when line was added was created (may be used to update buy_price_ht when future invoice will be created)

  fk_user_author        integer       NOT NULL DEFAULT 0,
  fk_user_ouverture     integer,
  fk_user_cloture       integer,
  commentaire           text,
  fk_unit               integer       DEFAULT NULL,

  fk_multicurrency		integer,
  multicurrency_code			varchar(255),
  multicurrency_subprice		double(24,8) DEFAULT 0,
  multicurrency_total_ht		double(24,8) DEFAULT 0,
  multicurrency_total_tva	double(24,8) DEFAULT 0,
  multicurrency_total_ttc	double(24,8) DEFAULT 0  
)ENGINE=innodb;
