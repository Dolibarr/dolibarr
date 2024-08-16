-- ============================================================================
-- Copyright (C) 2023 Laurent Destailleur  <eldy@users.sourceforge.net>
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


ALTER TABLE llx_supplier_proposal ADD UNIQUE INDEX uk_supplier_proposal_ref (ref, entity);

ALTER TABLE llx_supplier_proposal ADD INDEX idx_supplier_proposal_fk_soc (fk_soc);
ALTER TABLE llx_supplier_proposal ADD INDEX idx_supplier_proposal_fk_user_author (fk_user_author);
ALTER TABLE llx_supplier_proposal ADD INDEX idx_supplier_proposal_fk_user_valid (fk_user_valid);
ALTER TABLE llx_supplier_proposal ADD INDEX idx_supplier_proposal_fk_projet (fk_projet);
ALTER TABLE llx_supplier_proposal ADD INDEX idx_supplier_proposal_fk_account(fk_account);

--ALTER TABLE llx_propal ADD CONSTRAINT fk_propal_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
--ALTER TABLE llx_propal ADD CONSTRAINT fk_propal_fk_user_author	FOREIGN KEY (fk_user_author) REFERENCES llx_user (rowid);
--ALTER TABLE llx_propal ADD CONSTRAINT fk_propal_fk_user_valid	FOREIGN KEY (fk_user_valid)  REFERENCES llx_user (rowid);
--ALTER TABLE llx_propal ADD CONSTRAINT fk_propal_fk_user_signature   FOREIGN KEY (fk_user_signature) REFERENCES llx_user (rowid);
--ALTER TABLE llx_propal ADD CONSTRAINT fk_propal_fk_user_cloture	FOREIGN KEY (fk_user_cloture) REFERENCES llx_user (rowid);
--ALTER TABLE llx_propal ADD CONSTRAINT fk_propal_fk_projet		FOREIGN KEY (fk_projet) REFERENCES llx_projet (rowid);
--ALTER TABLE llx_propal ADD CONSTRAINT fk_propal_fk_warehouse 	FOREIGN KEY (fk_warehouse) REFERENCES llx_entrepot(rowid);
