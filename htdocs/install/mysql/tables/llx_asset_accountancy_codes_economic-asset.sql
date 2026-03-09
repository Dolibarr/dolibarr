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
-- Table to store the configuration of the accounting accounts of a fixed asset for economic status (fk_asset will be filled and fk_asset_model will be null)
-- or to store the configuration of the accounting accounts for templates of asset (fk_asset_model will be filled and fk_asset will be null)
--
-- Data example:
-- INSERT INTO llx_asset_accountancy_codes_economic (fk_asset, fk_asset_model, asset, depreciation_asset, depreciation_expense, value_asset_sold, receivable_on_assignment, proceeds_from_sales, vat_collected, vat_deductible, tms, fk_user_modif) VALUES
-- (1, NULL, '2183', '2818', '68112', '675', '465', '775', '44571', '44562', '2022-01-18 14:20:20', 1);

CREATE TABLE llx_asset_accountancy_codes_economic(
    rowid						integer			AUTO_INCREMENT PRIMARY KEY NOT NULL,
    fk_asset					integer,
    fk_asset_model				integer,

    asset						varchar(32),
    depreciation_asset			varchar(32),
    depreciation_expense		varchar(32),
    value_asset_sold			varchar(32),
    receivable_on_assignment	varchar(32),
    proceeds_from_sales			varchar(32),
    vat_collected				varchar(32),
    vat_deductible				varchar(32),
    tms                         timestamp       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_modif				integer
) ENGINE=innodb;
