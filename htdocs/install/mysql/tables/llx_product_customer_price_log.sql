-- ============================================================================
-- Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2013		Florian Henry			<florian.henry@open-concept.pro>
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

create table llx_product_customer_price_log
(
  rowid                       integer AUTO_INCREMENT PRIMARY KEY,
  entity				integer DEFAULT 1 NOT NULL,	   -- multi company id
  datec                       datetime,
  fk_product			integer NOT NULL,
  fk_soc				integer NOT NULL,	   
  price						double(24,8) DEFAULT 0,
  price_ttc					double(24,8) DEFAULT 0,
  price_min					double(24,8) DEFAULT 0,
  price_min_ttc				double(24,8) DEFAULT 0,
  price_base_type			varchar(3)   DEFAULT 'HT',
  tva_tx					double(6,3),
  recuperableonly           integer NOT NULL DEFAULT '0',   -- Other NPR VAT
  localtax1_tx				double(6,3)  DEFAULT 0,         -- Other local VAT 1 
  localtax1_type            varchar(10)  NOT NULL DEFAULT '0',
  localtax2_tx				double(6,3)  DEFAULT 0,         -- Other local VAT 2
  localtax2_type            varchar(10)  NOT NULL DEFAULT '0',
  fk_user				integer,
 import_key			varchar(14)                  -- Import key
)ENGINE=innodb;
