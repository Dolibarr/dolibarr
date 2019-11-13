-- ============================================================================
-- Copyright (C) 2007 Patrick Raguin       <patrick.raguin@gmail.com>
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

ALTER TABLE llx_categorie_project ADD PRIMARY KEY pk_categorie_project (fk_categorie, fk_project);
ALTER TABLE llx_categorie_project ADD INDEX idx_categorie_project_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_project ADD INDEX idx_categorie_project_fk_project (fk_project);

ALTER TABLE llx_categorie_project ADD CONSTRAINT fk_categorie_project_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_project ADD CONSTRAINT fk_categorie_project_fk_project_rowid FOREIGN KEY (fk_project) REFERENCES llx_projet (rowid);
