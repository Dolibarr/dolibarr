-- ============================================================================
-- Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2008-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
-- Copyright (C) 2010      juanjo Menent        <jmenent@2byte.es>
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
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- $Id$
-- ============================================================================

create table llx_product
(
  rowid						integer AUTO_INCREMENT PRIMARY KEY,
  datec						datetime,
  tms						timestamp,
  virtual					tinyint	  DEFAULT 0 NOT NULL,	-- value 0 for physical product, 1 for virtual product
  fk_parent					integer	  DEFAULT 0,			-- virtual product id
  ref						varchar(32)  NOT NULL,
  entity					integer	  DEFAULT 1 NOT NULL,	-- multi company id
  label						varchar(255) NOT NULL,
  description				text,
  note						text,
  price						double(24,8) DEFAULT 0,
  price_ttc					double(24,8) DEFAULT 0,
  price_min					double(24,8) DEFAULT 0,
  price_min_ttc				double(24,8) DEFAULT 0,
  price_base_type			varchar(3)   DEFAULT 'HT',
  tva_tx					double(6,3),
  recuperableonly           integer NOT NULL DEFAULT '0',   -- Franch NPR VAT
  localtax1_tx				double(6,3)  DEFAULT 0,         -- Spanish local VAT 1 
  localtax2_tx				double(6,3)  DEFAULT 0,         -- Spanish local VAT 2
  fk_user_author			integer,
  envente					tinyint      DEFAULT 1,
  tobuy						tinyint      DEFAULT 1,
  fk_product_type			integer      DEFAULT 0,			-- Type 0 for regular product, 1 for service
  duration					varchar(6),
  seuil_stock_alerte		integer      DEFAULT 0,
  barcode					varchar(255) DEFAULT NULL,
  fk_barcode_type			integer      DEFAULT 0,
  accountancy_code_sell		varchar(15),					-- code compta vente
  accountancy_code_buy		varchar(15),					-- code compta achat
  partnumber				varchar(32),
  weight					float        DEFAULT NULL,
  weight_units				tinyint      DEFAULT NULL,
  length					float        DEFAULT NULL,
  length_units				tinyint      DEFAULT NULL,
  surface					float        DEFAULT NULL,
  surface_units				tinyint      DEFAULT NULL,
  volume					float        DEFAULT NULL,
  volume_units				tinyint      DEFAULT NULL,
  stock						integer,						-- physical stock
  pmp						double(24,8) DEFAULT 0 NOT NULL,
  canvas					varchar(32)  DEFAULT 'default@product',
  finished					tinyint      DEFAULT NULL,
  hidden					tinyint      DEFAULT 0,			-- Need permission see also hidden products
  import_key				varchar(14)						-- import key
)type=innodb;
