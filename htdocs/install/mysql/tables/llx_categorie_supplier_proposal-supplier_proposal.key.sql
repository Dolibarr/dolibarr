-- ============================================================================
-- Copyright (C) 2007 Patrick Raguin     <patrick.raguin@gmail.com>
-- Copyright (C) 2012 Juanjo Menent      <jmenent@2byte.es>
-- Copyright (C) 2022 Solution Libre SAS <contact@solution-libre.fr>
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

ALTER TABLE llx_categorie_supplier_proposal ADD PRIMARY KEY pk_categorie_supplier_proposal (fk_categorie, fk_supplier_proposal);
ALTER TABLE llx_categorie_supplier_proposal ADD INDEX idx_categorie_supplier_proposal_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_supplier_proposal ADD INDEX idx_categorie_supplier_proposal_fk_supplier_proposal (fk_supplier_proposal);

ALTER TABLE llx_categorie_supplier_proposal ADD CONSTRAINT fk_categorie_supplier_proposal_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_supplier_proposal ADD CONSTRAINT fk_categorie_supplier_proposal_fk_supplier_proposal_rowid FOREIGN KEY (fk_supplier_proposal) REFERENCES llx_supplier_proposal (rowid);
