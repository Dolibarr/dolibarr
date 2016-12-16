-- ============================================================================
-- Copyright (C) 2014	Laurent Destailleur		<eldy@users.sourceforge.net>
-- Copyright (C) 2014	Florian Henry			<florian.henry@open-concept.pro>
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


ALTER TABLE llx_product_customer_price ADD INDEX idx_product_customer_price_fk_user (fk_user);
ALTER TABLE llx_product_customer_price ADD INDEX idx_product_customer_price_fk_soc (fk_soc);
ALTER TABLE llx_product_customer_price ADD UNIQUE INDEX uk_customer_price_fk_product_fk_soc (fk_product, fk_soc);

ALTER TABLE llx_product_customer_price ADD CONSTRAINT fk_product_customer_price_fk_user FOREIGN KEY (fk_user) REFERENCES llx_user (rowid);
ALTER TABLE llx_product_customer_price ADD CONSTRAINT fk_product_customer_price_fk_product FOREIGN KEY (fk_product) REFERENCES llx_product(rowid);
ALTER TABLE llx_product_customer_price ADD CONSTRAINT fk_product_customer_price_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe(rowid);
