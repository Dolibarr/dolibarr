-- Copyright (C) 2018      Alexandre Spangaro   <aspangaro@zendsi.com>
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


ALTER TABLE llx_asset ADD INDEX idx_asset_rowid (rowid);
ALTER TABLE llx_asset ADD INDEX idx_asset_ref (ref);
ALTER TABLE llx_asset ADD INDEX idx_asset_entity (entity);

ALTER TABLE llx_asset ADD INDEX idx_asset_fk_asset_type (fk_asset_type);
ALTER TABLE llx_asset ADD CONSTRAINT fk_asset_asset_type FOREIGN KEY (fk_asset_type)    REFERENCES llx_asset_type (rowid);

