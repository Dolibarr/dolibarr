-- ============================================================================
-- Copyright (C) 2021 Maxime Kohlhaas       <support@atm-consulting.fr>
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

ALTER TABLE llx_element_tag ADD UNIQUE INDEX idx_element_tag_uk (fk_categorie, fk_element);

ALTER TABLE llx_element_tag ADD CONSTRAINT fk_element_tag_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
