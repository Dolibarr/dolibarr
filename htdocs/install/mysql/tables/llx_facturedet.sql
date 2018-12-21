-- ===================================================================
-- Copyright (C) 2001-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
-- Copyright (C) 2004-2005	Laurent Destailleur		<eldy@users.sourceforge.net>
-- Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
-- Copyright (C) 2010		Juanjo Menent			<jmenent@2byte.es>
-- Copyright (C) 2012       Cédric Salvador       <csalvador@gpcsolutions.fr>
-- Copyright (C) 2014       Raphaël Doursenaud    <rdoursenaud@gpcsolutions.fr>
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
  fk_parent_line				integer	   NULL,
  fk_product					integer    NULL,					-- Doit pouvoir etre nul pour ligne detail sans produits
  label							varchar(255) DEFAULT NULL,
  description					text,
  vat_src_code					varchar(10)  DEFAULT '',			-- Vat code used as source of vat fields. Not strict foreign key here.
  tva_tx						double(6,3),						-- Vat rate (example 20%)
  localtax1_tx               	double(6,3)  DEFAULT 0,    		 	-- localtax1 rate
  localtax1_type			 	varchar(10)	 NULL, 				 	-- localtax1 type
  localtax2_tx               	double(6,3)  DEFAULT 0,    		 	-- localtax2 rate
  localtax2_type			 	varchar(10)	 NULL, 				 	-- localtax2 type
  qty							real,								-- Quantity (exemple 2)
  remise_percent				real       DEFAULT 0,				-- % de la remise ligne (exemple 20%)
  remise						real       DEFAULT 0,				-- Montant calcule de la remise % sur PU HT (exemple 20)
  fk_remise_except				integer    NULL,					-- Lien vers table des remises fixes
  subprice						double(24,8),						-- P.U. HT (exemple 100)
  price							double(24,8),						-- Deprecated (Do not use)
  total_ht						double(24,8),						-- Total HT de la ligne toute quantite et incluant remise ligne et globale
  total_tva						double(24,8),						-- Total TVA de la ligne toute quantite et incluant remise ligne et globale
  total_localtax1				double(24,8) DEFAULT 0,				-- Total LocalTax1 for total quantity of line
  total_localtax2				double(24,8) DEFAULT 0,				-- Total LocalTax2 for total quantity of line
  total_ttc						double(24,8),						-- Total TTC de la ligne toute quantite et incluant remise ligne et globale
  product_type					integer    DEFAULT 0,				-- 0 or 1. Value 9 may be used by some modules (amount of line may not be included into generated discount if value is 9).
  date_start					datetime   DEFAULT NULL,			-- date start if service
  date_end						datetime   DEFAULT NULL,			-- date end if service
  info_bits						integer    DEFAULT 0,				-- VAT NPR or not (for france only)

  buy_price_ht					double(24,8) DEFAULT 0,				-- buying price. Note: this value is saved as an always positive value, even on credit notes (it is price we bought the product before selling it).
  fk_product_fournisseur_price	integer      DEFAULT NULL,			-- reference of supplier price when line was added (may be used to update buy_price_ht current price when future invoice will be created)

  fk_code_ventilation			integer    DEFAULT 0 NOT NULL,		-- Id in table llx_accounting_bookeeping to know accounting account for product line
  
  special_code					integer    DEFAULT 0,				-- code for special lines (may be 1=transport, 2=ecotax, 3=option, moduleid=...)
  rang							integer    DEFAULT 0,				-- position of line
  fk_contract_line  			integer NULL,						-- id of contract line when invoice comes from contract lines
  import_key					varchar(14),

  situation_percent real,   										-- % progression of lines invoicing
  fk_prev_id        integer, 										-- id of the line in the previous situation,
  fk_unit           integer DEFAULT NULL, 							-- id of the unit code¡
  fk_user_author	integer,                						-- user making creation
  fk_user_modif     integer,                						-- user making last change

  fk_multicurrency				integer,
  multicurrency_code			varchar(255),
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
--
