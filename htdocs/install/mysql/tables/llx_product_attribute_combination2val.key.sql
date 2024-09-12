-- ============================================================================
-- Copyright (C) 2024      Maxime Kohlhaas  <maxime@atm-consulting.fr>
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

ALTER TABLE llx_product_attribute_combination2val ADD INDEX idx_product_att_com2v_prod_combination (fk_prod_combination);
ALTER TABLE llx_product_attribute_combination2val ADD INDEX idx_product_att_com2v_prod_attr (fk_prod_attr);
ALTER TABLE llx_product_attribute_combination2val ADD INDEX idx_product_att_com2v_prod_attr_val (fk_prod_attr_val);
