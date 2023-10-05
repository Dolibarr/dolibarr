-- ===================================================================
-- Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
-- Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
-- Copyright (C) 2006-2009	Laurent Destailleur		<eldy@users.sourceforge.net>
-- Copyright (C) 2010		Juanjo Menent			<jmenent@2byte.es>
-- Copyright (C) 2012      Cédric Salvador      <csalvador@gpcsolutions.fr>
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
-- ===================================================================

create table llx_commandedet
(
  rowid							integer AUTO_INCREMENT PRIMARY KEY,
  fk_commande					integer NOT NULL,
  fk_parent_line				integer NULL,
  fk_product					integer	 NULL,
  label							varchar(255) DEFAULT NULL,
  description					text,
  vat_src_code					varchar(10)  DEFAULT '',		 -- Vat code used as source of vat fields. Not strict foreign key here.
  tva_tx						double(7,4),	                 -- Vat rate
  localtax1_tx					double(7,4)  DEFAULT 0,			 -- localtax1 rate
  localtax1_type			 	varchar(10)  NULL, 				 -- localtax1 type
  localtax2_tx					double(7,4)  DEFAULT 0,			 -- localtax2 rate
  localtax2_type			 	varchar(10)	  	 NULL, 			 -- localtax2 type
  qty							real,                            -- quantity
  remise_percent				real         DEFAULT 0,          -- pourcentage de remise
  remise						real         DEFAULT 0,          -- montant de la remise
  fk_remise_except				integer      NULL,               -- Lien vers table des remises fixes
  price							real,                            -- prix final
  subprice						double(24,8) DEFAULT 0,          -- P.U. HT (exemple 100)
  total_ht						double(24,8) DEFAULT 0,          -- Total HT de la ligne toute quantite et incluant remise ligne et globale
  total_tva						double(24,8) DEFAULT 0,          -- Total TVA de la ligne toute quantite et incluant remise ligne et globale
  total_localtax1				double(24,8) DEFAULT 0,          -- Total LocalTax1 
  total_localtax2				double(24,8) DEFAULT 0,          -- Total LocalTax2
  total_ttc						double(24,8) DEFAULT 0,          -- Total TTC de la ligne toute quantite et incluant remise ligne et globale
  product_type					integer      DEFAULT 0,          -- 0 or 1. Value 9 may be used by some modules (amount of line may not be included into generated discount if value is 9).
  date_start					datetime     DEFAULT NULL,       -- date debut si service
  date_end						datetime     DEFAULT NULL,       -- date fin si service
  info_bits						integer      DEFAULT 0,          -- TVA NPR ou non

  buy_price_ht					double(24,8) DEFAULT 0,          -- buying price
  fk_product_fournisseur_price	integer      DEFAULT NULL,       -- reference of supplier price when line was added (may be used to update buy_price_ht current price when future invoice will be created)
  
  special_code					integer      DEFAULT 0,          -- code for special lines (may be 1=transport, 2=ecotax, 3=option, moduleid=...)
  rang							integer      DEFAULT 0,
  fk_unit						integer      DEFAULT NULL,       -- lien vers table des unités
  import_key					varchar(14),
  ref_ext                       varchar(255) DEFAULT NULL,
  
  fk_commandefourndet			integer DEFAULT NULL,            -- link to detail line of commande fourn (resplenish)
  
  fk_multicurrency				integer,
  multicurrency_code			varchar(3),
  multicurrency_subprice		double(24,8) DEFAULT 0,
  multicurrency_total_ht		double(24,8) DEFAULT 0,
  multicurrency_total_tva		double(24,8) DEFAULT 0,
  multicurrency_total_ttc		double(24,8) DEFAULT 0
)ENGINE=innodb;

-- 
-- List of codes for special_code
--
-- 1 : frais de port
-- 2 : ecotaxe
-- 3 : produit/service propose en option
--
