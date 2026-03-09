-- ============================================================================
-- Copyright (C) 2002-2006  Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2008-2017  Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2005-2010  Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2010       Juanjo Menent        <jmenent@2byte.es>
-- Copyright (C) 2012-2013  Cédric Salvador      <csalvador@gpcsolutions.fr>
-- Copyright (C) 2014       Marcos García        <marcosgdf@gmail.com>
-- Copyright (C) 2023       Alexandre Spangaro   <aspangaro@easya.solutions>
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

create table llx_product
(
  rowid                         integer AUTO_INCREMENT PRIMARY KEY,
  ref                           varchar(128)  NOT NULL,
  entity                        integer   DEFAULT 1 NOT NULL,       -- Multi company id

  ref_ext                       varchar(128),                       -- reference into an external system (not used by dolibarr)

  datec                         datetime,
  tms                           timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_parent                     integer	  DEFAULT 0,                -- Not used. Used by external modules. Virtual product id

  label                         varchar(255) NOT NULL,
  description                   text,
  note_public                   text,
  note                          text,
  customcode                    varchar(32),                        -- Optional custom code
  fk_country                    integer DEFAULT NULL,               -- Optional id of original country
  fk_state                      integer DEFAULT NULL,               -- Optional id of original state/province
  price                         double(24,8) DEFAULT 0,				-- price without tax
  price_ttc                     double(24,8) DEFAULT 0,				-- price inc vat (but not localtax1 nor localtax2)
  price_min                     double(24,8) DEFAULT 0,
  price_min_ttc                 double(24,8) DEFAULT 0,
  price_base_type               varchar(3)   DEFAULT 'HT',
  price_label                   varchar(255),
  cost_price                    double(24,8) DEFAULT NULL,          -- Cost price without tax. Can be used for margin calculation.
  default_vat_code              varchar(10),                        -- Same code than into table llx_c_tva (but no constraints). Should be used in priority to find default vat, npr, localtaxes for product.
  tva_tx                        double(7,4),                        -- Default VAT rate of product
  recuperableonly               integer NOT NULL DEFAULT '0',       -- French NPR VAT
  localtax1_tx                  double(7,4)  DEFAULT 0,
  localtax1_type                varchar(10)  NOT NULL DEFAULT '0',
  localtax2_tx                  double(7,4)  DEFAULT 0,
  localtax2_type                varchar(10)  NOT NULL DEFAULT '0',
  fk_user_author                integer DEFAULT NULL,               -- user making creation
  fk_user_modif                 integer,                            -- user making last change
  tosell                        tinyint      DEFAULT 1,             -- Product you sell
  tobuy                         tinyint      DEFAULT 1,             -- Product you buy
  tobatch                       tinyint      DEFAULT 0 NOT NULL,    -- Is it a product that need a batch management (eat-by or lot management)
  sell_or_eat_by_mandatory      tinyint      DEFAULT 0 NOT NULL,    -- Make sell-by or eat-by date mandatory
  batch_mask			        varchar(32)  DEFAULT NULL,          -- If the product has batch feature, you may want to use a batch mask per product
  fk_product_type               integer      DEFAULT 0,             -- Type of product: 0 for regular product, 1 for service, 9 for other (used by external module)
  duration                      varchar(6),
  seuil_stock_alerte            float      DEFAULT NULL,
  url                           varchar(255),
  barcode                       varchar(180) DEFAULT NULL,          -- barcode
  fk_barcode_type               integer      DEFAULT NULL,          -- barcode type
  accountancy_code_sell         varchar(32),                        -- Selling accountancy code
  accountancy_code_sell_intra   varchar(32),                        -- Selling accountancy code for vat intra-community
  accountancy_code_sell_export  varchar(32),                        -- Selling accountancy code for vat export
  accountancy_code_buy          varchar(32),                        -- Buying accountancy code
  accountancy_code_buy_intra    varchar(32),                        -- Buying accountancy code for vat intra-community
  accountancy_code_buy_export   varchar(32),                        -- Buying accountancy code for vat export
  partnumber                    varchar(32),                        -- Part/Serial number. TODO To use it into screen if not a duplicate of barcode.
  net_measure                   float        DEFAULT NULL,
  net_measure_units             tinyint      DEFAULT NULL,
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
  stockable_product             integer      DEFAULT 1 NOT NULL,
  stock                         real,                               -- Current physical stock (DENORMALIZED FIELD)
  pmp                           double(24,8) DEFAULT 0 NOT NULL,    -- To store valuation of stock calculated using average price method, for this product
  fifo                          double(24,8),                       -- To store valuation of stock calculated using fifo method, for this product. TODO Not used, should be replaced by stock value stored into movement table.
  lifo                          double(24,8),                       -- To store valuation of stock calculated using lifo method, for this product. TODO Not used, should be replaced by stock value stored into movement table.
  fk_default_warehouse          integer      DEFAULT NULL,
  fk_default_bom                integer      DEFAULT NULL,
  fk_default_workstation        integer      DEFAULT NULL,
  canvas                        varchar(32)  DEFAULT NULL,
  finished                      tinyint      DEFAULT NULL,          -- see dictionary c_product_nature
  lifetime                      integer      DEFAULT NULL,
  qc_frequency 					integer 	 DEFAULT NULL,			-- Quality control periodicity
  hidden                        tinyint      DEFAULT 0,             -- Not used. Deprecated.
  import_key                    varchar(14),                        -- Import key
  model_pdf                     varchar(255),                       -- model save document used
  fk_price_expression           integer,                            -- Link to the rule for dynamic price calculation
  desiredstock                  float        DEFAULT 0,
  fk_unit                       integer      DEFAULT NULL,
  price_autogen                 tinyint      DEFAULT 0,
  fk_project                    integer      DEFAULT NULL,           -- Used when product was generated by a project or is specific to a project
  mandatory_period              tinyint      DEFAULT 0,              -- is used to signal to the user that the start and end dates are mandatory for this type of product the fk_product_type == 1 (service) (non-blocking action)
  last_main_doc                 varchar(255)
)ENGINE=innodb;
