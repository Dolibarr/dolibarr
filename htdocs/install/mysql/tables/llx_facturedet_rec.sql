-- ===================================================================
-- Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
-- Copyright (C) 2009-2014  Laurent Destailleur		<eldy@users.sourceforge.net>
-- Copyright (C) 2010		Juanjo Menent			<jmenent@2byte.es>
-- Copyright (C) 2010-2012	Regis Houssin			<regis.houssin@inodbox.com>
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

create table llx_facturedet_rec
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  fk_facture		integer NOT NULL,
  fk_parent_line	integer NULL,
  fk_product		integer NULL,
  product_type		integer DEFAULT 0,
  label				varchar(255) DEFAULT NULL,
  description		text,
  vat_src_code					varchar(10)  DEFAULT '',			-- Vat code used as source of vat fields. Not strict foreign key here.
  tva_tx			double(7,4),	             	-- taux tva
  localtax1_tx      double(7,4) DEFAULT 0,    		-- localtax1 rate
  localtax1_type	varchar(10) NULL, 				-- localtax1 type
  localtax2_tx      double(7,4) DEFAULT 0,    		-- localtax2 rate
  localtax2_type	varchar(10)	 NULL, 				-- localtax2 type
  qty				real,							-- quantity
  remise_percent	real DEFAULT 0,				-- pourcentage de remise
  remise			real DEFAULT 0,				-- montant de la remise
  subprice			double(24,8),					-- prix avant remise
  price				double(24,8),					-- prix final
  total_ht			double(24,8),					-- Total HT de la ligne toute quantity et incluant remise ligne et globale
  total_tva			double(24,8),					-- Total TVA de la ligne toute quantity et incluant remise ligne et globale
  total_localtax1	double(24,8) DEFAULT 0,		-- Total LocalTax1 for total quantity of line
  total_localtax2	double(24,8) DEFAULT 0,		-- total LocalTax2 for total quantity of line
  total_ttc			double(24,8),					-- Total TTC de la ligne toute quantity et incluant remise ligne et globale
  date_start_fill	integer DEFAULT 0,				-- 1=autofill the date_start of invoice with __INVOICE_DATE_NEXT_INVOICE_BEFORE_GEN__
  date_end_fill		integer DEFAULT 0,				-- 1=autofill the date_start of invoice with __INVOICE_PREVIOUS_DATE_NEXT_INVOICE_AFTER_GEN__
  info_bits			integer DEFAULT 0,				-- TVA NPR ou non

  buy_price_ht					double(24,8) DEFAULT 0,				-- buying price. Note: this value is saved as an always positive value, even on credit notes (it is price we bought the product before selling it).
  fk_product_fournisseur_price	integer      DEFAULT NULL,			-- reference of supplier price when line was added (may be used to update buy_price_ht current price when future invoice will be created)
  
  special_code		integer UNSIGNED DEFAULT 0,		-- code for special lines
  rang				integer DEFAULT 0,				-- ordre d'affichage
  fk_contract_line  integer NULL,					-- id of contract line when template invoice comes from contract lines
  fk_unit           integer    DEFAULT NULL,
  import_key		varchar(14),
 
  fk_user_author	integer,                						-- user making creation
  fk_user_modif     integer,                						-- user making last change
    
  fk_multicurrency          integer,
  multicurrency_code        varchar(3),
  multicurrency_subprice    double(24,8) DEFAULT 0,
  multicurrency_total_ht    double(24,8) DEFAULT 0,
  multicurrency_total_tva   double(24,8) DEFAULT 0,
  multicurrency_total_ttc   double(24,8) DEFAULT 0
)ENGINE=innodb;
