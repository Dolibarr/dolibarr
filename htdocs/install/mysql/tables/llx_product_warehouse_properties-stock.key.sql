-- ===================================================================
-- Copyright (C) 2026 Aloïs Micard  <a.micard@vold.lu>
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
-- ===================================================================

ALTER TABLE llx_product_warehouse_properties ADD INDEX idx_product_warehouse_properties_fk_product (fk_product);
ALTER TABLE llx_product_warehouse_properties ADD INDEX idx_product_warehouse_properties_fk_entrepot (fk_entrepot);

ALTER TABLE llx_product_warehouse_properties ADD CONSTRAINT fk_product_warehouse_properties_fk_product FOREIGN KEY (fk_product) REFERENCES llx_product (rowid);
ALTER TABLE llx_product_warehouse_properties ADD CONSTRAINT fk_product_warehouse_properties_fk_entrepot FOREIGN KEY (fk_entrepot) REFERENCES llx_entrepot (rowid);
