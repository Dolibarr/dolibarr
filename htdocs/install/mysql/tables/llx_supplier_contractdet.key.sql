-- ============================================================================
-- Copyright (C) 2024      Support              <support@easya.solutions>
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


ALTER TABLE llx_supplier_contractdet ADD INDEX idx_supplier_contractdet_fk_supplier_contract (fk_supplier_contract);
ALTER TABLE llx_supplier_contractdet ADD INDEX idx_supplier_contractdet_fk_product (fk_product);
ALTER TABLE llx_supplier_contractdet ADD INDEX idx_supplier_contractdet_date_planned_opening (date_planned_opening);
ALTER TABLE llx_supplier_contractdet ADD INDEX idx_supplier_contractdet_date_opening (date_opening);
ALTER TABLE llx_supplier_contractdet ADD INDEX idx_supplier_contractdet_date_expiry (date_expiry);
ALTER TABLE llx_supplier_contractdet ADD INDEX idx_supplier_contractdet_status (status);

ALTER TABLE llx_supplier_contractdet ADD CONSTRAINT fk_supplier_contractdet_fk_supplier_contract FOREIGN KEY (fk_supplier_contract) REFERENCES llx_supplier_contract (rowid);
ALTER TABLE llx_supplier_contractdet ADD CONSTRAINT fk_supplier_contractdet_fk_product FOREIGN KEY (fk_product) REFERENCES llx_product (rowid);

ALTER TABLE llx_supplier_contractdet ADD CONSTRAINT fk_supplier_contractdet_fk_unit FOREIGN KEY (fk_unit) REFERENCES llx_c_units (rowid);
