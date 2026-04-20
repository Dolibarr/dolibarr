-- ========================================================================
-- Copyright (C) 2022      OpenDSI              <support@open-dsi.fr>
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
-- ========================================================================

ALTER TABLE llx_asset_accountancy_codes_fiscal ADD INDEX idx_asset_acf_rowid (rowid);
ALTER TABLE llx_asset_accountancy_codes_fiscal ADD UNIQUE uk_asset_acf_fk_asset (fk_asset);
ALTER TABLE llx_asset_accountancy_codes_fiscal ADD UNIQUE uk_asset_acf_fk_asset_model (fk_asset_model);

ALTER TABLE llx_asset_accountancy_codes_fiscal ADD CONSTRAINT fk_asset_acf_asset		FOREIGN KEY (fk_asset)			REFERENCES llx_asset (rowid);
ALTER TABLE llx_asset_accountancy_codes_fiscal ADD CONSTRAINT fk_asset_acf_asset_model	FOREIGN KEY (fk_asset_model)	REFERENCES llx_asset_model (rowid);
ALTER TABLE llx_asset_accountancy_codes_fiscal ADD CONSTRAINT fk_asset_acf_user_modif	FOREIGN KEY (fk_user_modif)		REFERENCES llx_user (rowid);
