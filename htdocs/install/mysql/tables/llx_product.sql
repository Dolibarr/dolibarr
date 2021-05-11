-- ============================================================================
-- Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2008-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
-- Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
-- Copyright (C) 2012-2013 Cédric Salvador      <csalvador@gpcsolutions.fr>
-- Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
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
-- ============================================================================

create table llx_product
(
  rowid                         integer AUTO_INCREMENT PRIMARY KEY,
  ref                           varchar(128)  NOT NULL,
  entity                        integer   DEFAULT 1 NOT NULL,       -- Multi company id

  ref_ext                       varchar(128),                       -- reference into an external system (not used by dolibarr)

  datec                         datetime,
  tms                           timestamp,
  fk_parent                     integer	  DEFAULT 0,                -- Not used. Used by external modules. Virtual product id

  label                         varchar(255) NOT NULL,
  description                   text,
  note_public                   text,
  note                          text,
  customcode                    varchar(32),                        -- Optionnal custom code
  fk_country                    integer DEFAULT NULL,               -- Optionnal id of original country
  price                         double(24,8) DEFAULT 0,
  price_ttc                     double(24,8) DEFAULT 0,
  price_min                     double(24,8) DEFAULT 0,
  price_min_ttc                 double(24,8) DEFAULT 0,
  price_base_type               varchar(3)   DEFAULT 'HT',
  cost_price                    double(24,8) DEFAULT NULL,          -- Cost price without tax. Can be used for margin calculation.
  default_vat_code              varchar(10),                        -- Same code than into table llx_c_tva (but no constraints). Should be used in priority to find default vat, npr, localtaxes for product.
  tva_tx                        double(6,3),                        -- Default VAT rate of product
  recuperableonly               integer NOT NULL DEFAULT '0',       -- French NPR VAT
  localtax1_tx                  double(6,3)  DEFAULT 0,
  localtax1_type                varchar(10)  NOT NULL DEFAULT '0',
  localtax2_tx                  double(6,3)  DEFAULT 0, 
  localtax2_type                varchar(10)  NOT NULL DEFAULT '0',
  fk_user_author                integer DEFAULT NULL,               -- user making creation
  fk_user_modif                 integer,                            -- user making last change
  tosell                        tinyint      DEFAULT 1,             -- Product you sell
  tobuy                         tinyint      DEFAULT 1,             -- Product you buy
  onportal                      tinyint      DEFAULT 0,	            -- If it is a product you sell and you want to sell it on portal (module website must be on)
  tobatch                       tinyint      DEFAULT 0 NOT NULL,    -- Is it a product that need a batch management (eat-by or lot management)
  fk_product_type               integer      DEFAULT 0,             -- Type of product: 0 for regular product, 1 for service, 9 for other (used by external module)
  duration                      varchar(6),
  seuil_stock_alerte            integer      DEFAULT NULL,
  url                           varchar(255),
  barcode                       varchar(180) DEFAULT NULL,          -- barcode
  fk_barcode_type               integer      DEFAULT NULL,          -- barcode type
  accountancy_code_sell         varchar(32),                        -- Selling accountancy code
  accountancy_code_sell_intra   varchar(32),                        -- Selling accountancy code for vat intracommunity
  accountancy_code_sell_export  varchar(32),                        -- Selling accountancy code for vat export
  accountancy_code_buy          varchar(32),                        -- Buying accountancy code
  partnumber                    varchar(32),                        -- Part/Serial number. TODO To use it into screen if not a duplicate of barcode.
  weight                        float        DEFAULT NULL,
  weight_units                  tinyint      DEFAULT NULL,
  length                        float        DEFAULT NULL,
  length_units                  tinyint      DEFAULT NULL,
  width                         float        DEFAULT NULL,
  width_units                   tinyint      DEFAULT NULL,
  height                        float        DEFAULT NULL,
  height_units                  tinyint      DEFAULT NULL,
  surface                       float        DEFAULT NULL,
  surface_units                 tinyint      DEFAULT NULL,
  volume                        float        DEFAULT NULL,
  volume_units                  tinyint      DEFAULT NULL,
  stock                         real,                               -- Current physical stock (dernormalized field)
  pmp                           double(24,8) DEFAULT 0 NOT NULL,    -- To store valuation of stock calculated using average price method, for this product
  fifo                          double(24,8),                       -- To store valuation of stock calculated using fifo method, for this product. TODO Not used, should be replaced by stock value stored into movement table.
  lifo                          double(24,8),                       -- To store valuation of stock calculated using lifo method, for this product. TODO Not used, should be replaced by stock value stored into movement table.
  fk_default_warehouse          integer      DEFAULT NULL,
  canvas                        varchar(32)  DEFAULT NULL,
  finished                      tinyint      DEFAULT NULL,          -- 1=manufactured product, 0=matiere premiere
  hidden                        tinyint      DEFAULT 0,             -- Not used. Deprecated.
  import_key                    varchar(14),                        -- Import key
  model_pdf                     varchar(255),                       -- model save dodument used
  fk_price_expression           integer,                            -- Link to the rule for dynamic price calculation
  desiredstock                  integer      DEFAULT 0,
  fk_unit                       integer      DEFAULT NULL,
  price_autogen                 tinyint DEFAULT 0
)ENGINE=innodb;
