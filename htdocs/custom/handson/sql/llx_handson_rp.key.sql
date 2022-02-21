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
-- along with this program.  If not, see https://www.gnu.org/licenses/.


-- BEGIN MODULEBUILDER INDEXES
ALTER TABLE llx_handson_rp ADD INDEX idx_handson_rp_rowid (rowid);
ALTER TABLE llx_handson_rp ADD INDEX idx_handson_rp_ref (ref);
ALTER TABLE llx_handson_rp ADD INDEX idx_handson_rp_contacts (contacts);
ALTER TABLE llx_handson_rp ADD INDEX idx_handson_rp_season (season);
ALTER TABLE llx_handson_rp ADD INDEX idx_handson_rp_program (program);
ALTER TABLE llx_handson_rp ADD INDEX idx_handson_rp_region (region);
ALTER TABLE llx_handson_rp ADD INDEX idx_handson_rp_fk_soc (fk_soc);
ALTER TABLE llx_handson_rp ADD INDEX idx_handson_rp_shipping (shipping);
ALTER TABLE llx_handson_rp ADD INDEX idx_handson_rp_contract_adr (contract_adr);
ALTER TABLE llx_handson_rp ADD CONSTRAINT llx_handson_rp_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_handson_rp ADD UNIQUE INDEX uk_handson_rp_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_handson_rp ADD CONSTRAINT llx_handson_rp_fk_field FOREIGN KEY (fk_field) REFERENCES llx_handson_myotherobject(rowid);

