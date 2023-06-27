-- ============================================================================
-- Copyright (C) 2014 Marcos García <marcosgdf@gmail.com>
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

ALTER TABLE llx_product_price ADD INDEX idx_product_price_fk_user_author (fk_user_author);
ALTER TABLE llx_product_price ADD INDEX idx_product_price_fk_product (fk_product);

ALTER TABLE llx_product_price ADD CONSTRAINT fk_product_price_user_author FOREIGN KEY (fk_product) REFERENCES  llx_product (rowid);
--ALTER TABLE llx_product_price ADD CONSTRAINT fk_product_price_product FOREIGN KEY (fk_user_author) REFERENCES  llx_user (rowid);
