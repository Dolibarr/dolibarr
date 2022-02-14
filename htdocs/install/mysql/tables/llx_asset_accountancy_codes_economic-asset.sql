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
    tms							timestamp,
    fk_user_modif				integer
) ENGINE=innodb;
