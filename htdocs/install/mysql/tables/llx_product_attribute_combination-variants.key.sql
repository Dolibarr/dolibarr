-- ============================================================================
-- Copyright (C) 2019      Laurent Destailleur  <eldy@users.sourceforge.net>
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

ALTER TABLE llx_product_attribute_combination ADD INDEX idx_product_att_com_product_parent (fk_product_parent);
ALTER TABLE llx_product_attribute_combination ADD INDEX idx_product_att_com_product_child (fk_product_child);

ALTER TABLE llx_product_attribute_combination ADD CONSTRAINT fk_product_att_com_product_parent FOREIGN KEY (fk_product_parent) REFERENCES llx_product (rowid);

-- No foreign key and no unique index on (fk_product_parent, fk_product_child):
-- ProductCombination::createProductCombination() inserts the combination before the child
-- product is known and updates fk_product_child afterwards, so the column holds 0 for the
-- duration of the creation, and two concurrent creations for the same parent would both hold
-- (fk_product_parent, 0). The import engine only needs a SELECT on its update key, which the
-- index on fk_product_parent already serves.
