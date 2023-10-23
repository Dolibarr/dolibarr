-- ========================================================================
-- Copyright (C) 2021		Open-Dsi	<support@open-dsi.fr>
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
-- ========================================================================

create table llx_product_perentity
(
  rowid         				integer AUTO_INCREMENT PRIMARY KEY,
  fk_product	   				integer,
  entity             			integer DEFAULT 1 NOT NULL,      	-- multi company id
  default_vat_code              varchar(10),                        -- Same code than into table llx_c_tva (but no constraints). Should be used in priority to find default vat, npr, localtaxes for product.
  tva_tx                        double(7,4),                        -- Default VAT rate of product
  recuperableonly               integer NOT NULL DEFAULT '0',       -- French NPR VAT
  localtax1_tx                  double(7,4)  DEFAULT 0,
  localtax1_type                varchar(10)  NOT NULL DEFAULT '0',
  localtax2_tx                  double(7,4)  DEFAULT 0,
  localtax2_type                varchar(10)  NOT NULL DEFAULT '0',
  tosell                        tinyint      DEFAULT 1,             -- Product you sell
  tobuy                         tinyint      DEFAULT 1,             -- Product you buy
  url                           varchar(255),
  barcode                       varchar(180) DEFAULT NULL,          -- barcode
  fk_barcode_type               integer      DEFAULT NULL,          -- barcode type
  accountancy_code_sell         varchar(32),                        -- Selling accountancy code
  accountancy_code_sell_intra   varchar(32),                        -- Selling accountancy code for vat intracommunity
  accountancy_code_sell_export  varchar(32),                        -- Selling accountancy code for vat export
  accountancy_code_buy          varchar(32),                        -- Buying accountancy code
  accountancy_code_buy_intra    varchar(32),                        -- Buying accountancy code for vat intracommunity
  accountancy_code_buy_export   varchar(32),                  		-- Buying accountancy code for vat import
  pmp double(24,8)
)ENGINE=innodb;
