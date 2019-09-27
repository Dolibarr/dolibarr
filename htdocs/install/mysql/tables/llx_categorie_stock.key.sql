-- ============================================================================
-- Copyright (C) 2007 Patrick Raguin            <patrick.raguin@gmail.com>
-- Copyright (C) 2019 Quentin Vial-Gouteyron    <quentin.vial-gouteyron@atm-consulting.fr>
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

ALTER TABLE llx_categorie_entrepot ADD PRIMARY KEY pk_categorie_entrepot (fk_categorie, fk_entrepot);
ALTER TABLE llx_categorie_entrepot ADD INDEX idx_categorie_entrepot_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_entrepot ADD INDEX idx_categorie_entrepot_fk_entrepot (fk_entrepot);

ALTER TABLE llx_categorie_entrepot ADD CONSTRAINT fk_categorie_entrepot_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_entrepot ADD CONSTRAINT fk_categorie_entrepot_fk_entrepot_rowid FOREIGN KEY (fk_entrepot) REFERENCES llx_entrepot (rowid);
