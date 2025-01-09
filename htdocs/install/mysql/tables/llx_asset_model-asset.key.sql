-- ========================================================================
-- Copyright (C) 2022      OpenDSI              <support@open-dsi.fr>
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
-- ========================================================================

ALTER TABLE llx_asset_model ADD INDEX idx_asset_model_rowid (rowid);
ALTER TABLE llx_asset_model ADD INDEX idx_asset_model_entity (entity);
ALTER TABLE llx_asset_model ADD INDEX idx_asset_model_ref (ref);
ALTER TABLE llx_asset_model ADD INDEX idx_asset_model_pays (fk_pays);
ALTER TABLE llx_asset_model ADD UNIQUE INDEX uk_asset_model (entity, ref);

ALTER TABLE llx_asset_model ADD CONSTRAINT fk_asset_model_user_creat	FOREIGN KEY (fk_user_creat)		REFERENCES llx_user (rowid);
ALTER TABLE llx_asset_model ADD CONSTRAINT fk_asset_model_user_modif	FOREIGN KEY (fk_user_modif)		REFERENCES llx_user (rowid);
