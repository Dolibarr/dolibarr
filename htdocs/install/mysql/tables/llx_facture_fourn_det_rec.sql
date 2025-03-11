-- ===================================================================
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

create table llx_facture_fourn_det_rec
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  fk_facture_fourn		integer NOT NULL,
  fk_parent_line	integer NULL,
  fk_product		integer NULL,
  ref               varchar(50),   -- supplier product ref
  label				varchar(255) DEFAULT NULL,
  description		text,
  pu_ht             double(24,8), -- unit price excluding tax
  pu_ttc            double(24,8), -- unit price with tax
  qty               real,         -- quantity of product/service
  remise_percent	real       DEFAULT 0,				-- % de la remise ligne (exemple 20%)
  fk_remise_except	integer    NULL,					-- Lien vers table des remises fixes
  vat_src_code					varchar(10)  DEFAULT '',			-- Vat code used as source of vat fields. Not strict foreign key here.
  tva_tx			double(7,4),	             	-- taux tva
  localtax1_tx      double(7,4) DEFAULT 0,    		-- localtax1 rate
  localtax1_type	varchar(10) NULL, 				-- localtax1 type
  localtax2_tx      double(7,4) DEFAULT 0,    		-- localtax2 rate
  localtax2_type	varchar(10)	 NULL, 				-- localtax2 type
  total_ht			double(24,8),					-- Total HT de la ligne toute quantity et incluant remise ligne et globale
  total_tva			double(24,8),					-- Total TVA de la ligne toute quantity et incluant remise ligne et globale
  total_localtax1	double(24,8) DEFAULT 0,		-- Total LocalTax1 for total quantity of line
  total_localtax2	double(24,8) DEFAULT 0,		-- total LocalTax2 for total quantity of line
  total_ttc			double(24,8),					-- Total TTC de la ligne toute quantity et incluant remise ligne et globale
  product_type		integer DEFAULT 0,
  date_start        integer DEFAULT NULL,       -- date debut si service
  date_end          integer DEFAULT NULL,       -- date fin si service
  info_bits			integer DEFAULT 0,				-- TVA NPR ou non
  special_code		integer UNSIGNED DEFAULT 0,		-- code for special lines
  rang				integer DEFAULT 0,				-- ordre d'affichage
  fk_unit           integer    DEFAULT NULL,
  import_key		varchar(14),

  fk_user_author	integer,                						-- user making creation
  fk_user_modif     integer,                						-- user making last change

  fk_multicurrency          integer,
  multicurrency_code        varchar(3),
  multicurrency_subprice     double(24,8) DEFAULT 0,
  multicurrency_subprice_ttc double(24,8) DEFAULT 0,
  multicurrency_total_ht    double(24,8) DEFAULT 0,
  multicurrency_total_tva   double(24,8) DEFAULT 0,
  multicurrency_total_ttc   double(24,8) DEFAULT 0
)ENGINE=innodb;
