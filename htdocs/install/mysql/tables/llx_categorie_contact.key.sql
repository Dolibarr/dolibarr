-- ============================================================================
-- Copyright (C) 2013 Florian HENRY       <florian.henry@open-concept.pro>
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

ALTER TABLE llx_categorie_contact ADD PRIMARY KEY pk_categorie_contact (fk_categorie, fk_socpeople);
ALTER TABLE llx_categorie_contact ADD INDEX idx_categorie_contact_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_contact ADD INDEX idx_categorie_contact_fk_socpeople (fk_socpeople);

ALTER TABLE llx_categorie_contact ADD CONSTRAINT fk_categorie_contact_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_contact ADD CONSTRAINT fk_categorie_contact_fk_socpeople   FOREIGN KEY (fk_socpeople) REFERENCES llx_socpeople (rowid);
