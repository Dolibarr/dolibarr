-- ============================================================================
-- Copyright (C) 2022 Charlene Benke <charlene@patas-monkey.com>
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


ALTER TABLE llx_element_categorie ADD UNIQUE INDEX idx_element_categorie_idx (fk_element, fk_categorie);

ALTER TABLE llx_element_categorie ADD INDEX idx_element_categorie_fk_categorie (fk_categorie);

ALTER TABLE llx_element_categorie ADD CONSTRAINT fk_element_categorie_fk_categorie FOREIGN KEY (fk_categorie) REFERENCES llx_categorie(rowid);
