-- ============================================================================
-- Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
-- Copyright (C) 2009-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
-- Copyright (C) 2009-2013	Regis Houssin			<regis.houssin@inodbox.com>
-- Copyright (C) 2012		Juanjo Menent			<jmenent@2byte.es>
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

create table llx_product_fournisseur_price
(
  rowid					integer AUTO_INCREMENT PRIMARY KEY,
  entity				integer DEFAULT 1 NOT NULL,	   -- multi company id
  datec					datetime,
  tms					timestamp,
  fk_product			integer,
  fk_soc				integer,
  ref_fourn				varchar(30),
  desc_fourn            text,
  fk_availability		integer,	   
  price					double(24,8) DEFAULT 0,		-- price without tax for quantity
  quantity				double,
  remise_percent		double NOT NULL DEFAULT 0,
  remise				double NOT NULL DEFAULT 0,
  unitprice				double(24,8) DEFAULT 0,		-- unit price without tax
  charges				double(24,8) DEFAULT 0,		-- to store transport cost. Constant PRODUCT_CHARGES must be set to see it.
  default_vat_code	    varchar(10),
  tva_tx				double(6,3) NOT NULL,
  localtax1_tx		    double(6,3) DEFAULT 0,
  localtax1_type        varchar(10)  NOT NULL DEFAULT '0',
  localtax2_tx		    double(6,3) DEFAULT 0,
  localtax2_type        varchar(10)  NOT NULL DEFAULT '0',
  info_bits				integer NOT NULL DEFAULT 0,
  fk_user				integer,
  fk_supplier_price_expression	integer,            -- Link to the rule for dynamic price calculation
  import_key			varchar(14),                -- Import key
  delivery_time_days    integer,
  supplier_reputation varchar(10),
  
  fk_multicurrency		integer,
  multicurrency_code	varchar(255),
  multicurrency_tx			double(24,8) DEFAULT 1,
  multicurrency_unitprice   double(24,8) DEFAULT NULL,		-- unit price without tax
  multicurrency_price		double(24,8) DEFAULT NULL
)ENGINE=innodb;
