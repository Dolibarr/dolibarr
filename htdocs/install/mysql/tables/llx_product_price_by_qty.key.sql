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
-- ============================================================================

ALTER TABLE llx_product_price_by_qty ADD UNIQUE INDEX uk_product_price_by_qty_level (fk_product_price, quantity);

ALTER TABLE llx_product_price_by_qty ADD INDEX idx_product_price_by_qty_fk_product_price (fk_product_price);

ALTER TABLE llx_product_price_by_qty ADD CONSTRAINT fk_product_price_by_qty_fk_product_price FOREIGN KEY (fk_product_price) REFERENCES llx_product_price (rowid);