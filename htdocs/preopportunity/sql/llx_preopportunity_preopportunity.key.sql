-- Copyright (C) 2024 Johnson
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
ALTER TABLE llx_preopportunity_preopportunity ADD INDEX idx_preopportunity_preopportunity_rowid (rowid);
ALTER TABLE llx_preopportunity_preopportunity ADD INDEX idx_preopportunity_preopportunity_ref (ref);
ALTER TABLE llx_preopportunity_preopportunity ADD INDEX idx_preopportunity_preopportunity_status (status);
ALTER TABLE llx_preopportunity_preopportunity ADD INDEX idx_preopportunity_preopportunity_fk_soc (fk_soc);
ALTER TABLE llx_preopportunity_preopportunity ADD INDEX idx_preopportunity_preopportunity_fk_project (fk_project);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_preopportunity_preopportunity ADD UNIQUE INDEX uk_preopportunity_preopportunity_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_preopportunity_preopportunity ADD CONSTRAINT llx_preopportunity_preopportunity_fk_field FOREIGN KEY (fk_field) REFERENCES llx_preopportunity_myotherobject(rowid);

