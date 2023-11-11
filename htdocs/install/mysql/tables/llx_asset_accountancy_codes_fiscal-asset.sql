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
--
-- Table to store the configuration of the accounting accounts of a fixed asset for fiscal status
--
-- Data example:
-- INSERT INTO llx_asset_accountancy_codes_fiscal (fk_asset, fk_asset_model, accelerated_depreciation, endowment_accelerated_depreciation, provision_accelerated_depreciation, tms, fk_user_modif) VALUES
-- (1, NULL, NULL, NULL, NULL, '2022-01-18 14:20:20', 1);

CREATE TABLE llx_asset_accountancy_codes_fiscal(
    rowid									integer			AUTO_INCREMENT PRIMARY KEY NOT NULL,
    fk_asset								integer,
    fk_asset_model							integer,

    accelerated_depreciation				varchar(32),
    endowment_accelerated_depreciation		varchar(32),
    provision_accelerated_depreciation		varchar(32),

    tms                                     timestamp       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_modif							integer
) ENGINE=innodb;
