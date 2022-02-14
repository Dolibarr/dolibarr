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

CREATE TABLE llx_asset_depreciation_options_fiscal(
    rowid								integer			AUTO_INCREMENT PRIMARY KEY NOT NULL,
    fk_asset							integer,
    fk_asset_model						integer,

    depreciation_type					smallint		DEFAULT 0 NOT NULL,		-- 0:linear, 1:degressive, 2:exceptional
    degressive_coefficient				double(24,8),
    duration							smallint		NOT NULL,
    duration_type						smallint		DEFAULT 0  NOT NULL,	-- 0:annual, 1:monthly, 2:daily

	amount_base_depreciation_ht			double(24,8),
	amount_base_deductible_ht			double(24,8),
	total_amount_last_depreciation_ht	double(24,8),

	tms									timestamp,
	fk_user_modif						integer
) ENGINE=innodb;
