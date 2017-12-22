-- ============================================================================
-- Copyright (C) 2002-2003	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
-- Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
-- Copyright (C) 2010		Juanjo Menent			<jmenent@2byte.es>
-- Copyright (C) 2012		Maxime Kohlhaas			<maxime.kohlhaas@atm-consulting.fr>
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
-- This table is used to defined price by qty when a line into llx_product_price 
-- is set with price_by_qty = 1
-- ============================================================================

create table llx_product_price_by_qty
(
  rowid				integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
  fk_product_price	integer NOT NULL,
  price				double(24,8) DEFAULT 0,
  price_base_type	varchar(3) DEFAULT 'HT',
  quantity			double DEFAULT NULL,
  remise_percent	double NOT NULL DEFAULT 0,
  remise			double NOT NULL DEFAULT 0,
  unitprice			double(24,8) DEFAULT 0,
  fk_user_creat 	integer,
  fk_user_modif 	integer,

  fk_multicurrency		integer,
  multicurrency_code	varchar(255),
  multicurrency_tx			double(24,8) DEFAULT 1,
  multicurrency_price	double(24,8) DEFAULT NULL,
  multicurrency_price_ttc	double(24,8) DEFAULT NULL,
  
  tms				timestamp,
  import_key    	varchar(14)
)ENGINE=innodb;
