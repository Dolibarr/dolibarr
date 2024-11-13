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
ALTER TABLE llx_bom_bomline ADD INDEX idx_bom_bomline_rowid (rowid);
ALTER TABLE llx_bom_bomline ADD INDEX idx_bom_bomline_fk_product (fk_product);
ALTER TABLE llx_bom_bomline ADD INDEX idx_bom_bomline_fk_bom (fk_bom);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_bom_bomline ADD UNIQUE INDEX uk_bom_bomline_fieldxy(fieldx, fieldy);

ALTER TABLE llx_bom_bomline ADD CONSTRAINT llx_bom_bomline_fk_bom FOREIGN KEY (fk_bom) REFERENCES llx_bom_bom(rowid);
