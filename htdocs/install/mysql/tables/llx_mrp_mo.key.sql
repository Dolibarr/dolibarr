-- Copyright (C) ---Put here your own copyright and developer email---
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see http://www.gnu.org/licenses/.


-- BEGIN MODULEBUILDER INDEXES
ALTER TABLE llx_mrp_mo ADD INDEX idx_mrp_mo_ref (ref);
ALTER TABLE llx_mrp_mo ADD INDEX idx_mrp_mo_entity (entity);
ALTER TABLE llx_mrp_mo ADD INDEX idx_mrp_mo_fk_soc (fk_soc);
ALTER TABLE llx_mrp_mo ADD CONSTRAINT fk_mrp_mo_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_mrp_mo ADD INDEX idx_mrp_mo_status (status);
ALTER TABLE llx_mrp_mo ADD INDEX idx_mrp_mo_fk_product (fk_product);
ALTER TABLE llx_mrp_mo ADD INDEX idx_mrp_mo_date_start_planned (date_start_planned);
ALTER TABLE llx_mrp_mo ADD INDEX idx_mrp_mo_date_end_planned (date_end_planned);
ALTER TABLE llx_mrp_mo ADD INDEX idx_mrp_mo_fk_bom (fk_bom);
ALTER TABLE llx_mrp_mo ADD INDEX idx_mrp_mo_fk_project (fk_project);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_mrp_mo ADD UNIQUE INDEX uk_mrp_mo_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_mrp_mo ADD CONSTRAINT llx_mrp_mo_fk_field FOREIGN KEY (fk_field) REFERENCES llx_mrp_myotherobject(rowid);

