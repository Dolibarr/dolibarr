-- ============================================================================
-- Copyright (C) 2026		Quentin Vial-Gouteyron	<quentin.vial-gouteyron@atm-consulting.fr>
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

-- Fixed sell prices per currency for a product (independent from exchange rate).
-- One row per (product, price level, entity, currency).

create table llx_product_price_currency
(
  rowid					integer AUTO_INCREMENT PRIMARY KEY,
  entity				integer   DEFAULT 1 NOT NULL,			-- Multi company id
  tms					timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_product			integer NOT NULL,
  price_level			smallint  DEFAULT 1 NOT NULL,
  fk_multicurrency		integer,
  multicurrency_code	varchar(3) NOT NULL,
  multicurrency_tx		double(24,8) DEFAULT 1,					-- Exchange rate at input time (informative only, not used to compute the fixed price)
  price					double(24,8) DEFAULT NULL,				-- Fixed price without tax, in the currency
  price_ttc				double(24,8) DEFAULT NULL,				-- Fixed price inc tax, in the currency
  price_base_type		varchar(3) DEFAULT 'HT',
  date_price			datetime NOT NULL,
  fk_user_author		integer
)ENGINE=innodb;
