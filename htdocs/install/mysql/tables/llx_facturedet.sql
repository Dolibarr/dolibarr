-- ===================================================================
-- Copyright (C) 2001-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
-- Copyright (C) 2004-2005	Laurent Destailleur		<eldy@users.sourceforge.net>
-- Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
-- Copyright (C) 2010		Juanjo Menent			<jmenent@2byte.es>
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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ===================================================================


create table llx_facturedet
(
  rowid							integer    AUTO_INCREMENT PRIMARY KEY,
  fk_facture					integer    NOT NULL,
  fk_parent_line				integer	 NULL,
  fk_product					integer    NULL,					-- Doit pouvoir etre nul pour ligne detail sans produits
  label							varchar(255) DEFAULT NULL,
  description					text,
  tva_tx						double(6,3),						-- Taux tva produit/service (exemple 19.6)
  localtax1_tx               	double(6,3)  DEFAULT 0,    		 	-- localtax1 rate
  localtax1_type			 	varchar(10)	  	 NULL, 				 	-- localtax1 type
  localtax2_tx               	double(6,3)  DEFAULT 0,    		 	-- localtax2 rate
  localtax2_type			 	varchar(10)	  	 NULL, 				 	-- localtax2 type
  qty							real,								-- Quantity (exemple 2)
  remise_percent				real       DEFAULT 0,				-- % de la remise ligne (exemple 20%)
  remise						real       DEFAULT 0,				-- Montant calcule de la remise % sur PU HT (exemple 20)
  fk_remise_except				integer    NULL,					-- Lien vers table des remises fixes
  subprice						double(24,8),						-- P.U. HT (exemple 100)
  price							double(24,8),						-- Deprecated (Do not use)
  total_ht						double(24,8),						-- Total HT de la ligne toute quantite et incluant remise ligne et globale
  total_tva						double(24,8),						-- Total TVA de la ligne toute quantite et incluant remise ligne et globale
  total_localtax1				double(24,8)  	 DEFAULT 0,			-- Total LocalTax1 for total quantity of line
  total_localtax2				double(24,8)		 DEFAULT 0,		-- total LocalTax2 for total quantity of line
  total_ttc						double(24,8),						-- Total TTC de la ligne toute quantite et incluant remise ligne et globale
  product_type					integer    DEFAULT 0,
  date_start					datetime   DEFAULT NULL,			-- date debut si service
  date_end						datetime   DEFAULT NULL,			-- date fin si service
  info_bits						integer    DEFAULT 0,				-- TVA NPR ou non

  buy_price_ht					double(24,8) DEFAULT 0,				-- buying price
  fk_product_fournisseur_price	integer      DEFAULT NULL,			-- reference of supplier price when line was added (may be used to update buy_price_ht current price when future invoice will be created)

  fk_code_ventilation			integer    DEFAULT 0 NOT NULL,
  special_code					integer UNSIGNED DEFAULT 0,			-- code pour les lignes speciales
  rang							integer    DEFAULT 0,				-- ordre d'affichage
  import_key					varchar(14)
  
)ENGINE=innodb;

-- 
-- List of codes for special_code
--
-- 1 : frais de port
-- 2 : ecotaxe
--
