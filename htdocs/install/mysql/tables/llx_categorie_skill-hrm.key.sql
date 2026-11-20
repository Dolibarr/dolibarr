-- ============================================================================
-- Copyright (C) 2026       Laurent Magnin
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

ALTER TABLE llx_categorie_skill ADD PRIMARY KEY pk_categorie_skill (fk_categorie, fk_skill);
ALTER TABLE llx_categorie_skill ADD INDEX idx_categorie_skill_fk_skill (fk_skill);

ALTER TABLE llx_categorie_skill ADD CONSTRAINT fk_categorie_skill_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_skill ADD CONSTRAINT fk_categorie_skill_fk_skill_rowid FOREIGN KEY (fk_skill) REFERENCES llx_hrm_skill (rowid);
