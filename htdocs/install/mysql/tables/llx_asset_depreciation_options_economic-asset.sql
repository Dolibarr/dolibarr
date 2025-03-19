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
-- Table to store economical depreciation of a fixed asset
--
-- Data example:
-- INSERT INTO llx_asset_depreciation_options_economic (fk_asset, fk_asset_model, depreciation_type, accelerated_depreciation_option, degressive_coefficient, duration, duration_type, amount_base_depreciation_ht, amount_base_deductible_ht, total_amount_last_depreciation_ht, tms, fk_user_modif) VALUES
-- (1, NULL, 1, NULL, 1.75000000, 60, 1, 500.00000000, 0.00000000, 7.00000000, '2022-03-09 14:15:48', 1);

CREATE TABLE llx_asset_depreciation_options_economic(
    rowid                               integer         AUTO_INCREMENT PRIMARY KEY NOT NULL,
    fk_asset							integer,
    fk_asset_model						integer,

    depreciation_type					smallint		DEFAULT 0 NOT NULL,		-- 0:linear, 1:degressive, 2:exceptional
    accelerated_depreciation_option		boolean DEFAULT false,								-- activate accelerated depreciation mode (fiscal)
    degressive_coefficient				double(24,8),
    duration							smallint		NOT NULL,
    duration_type						smallint		DEFAULT 0  NOT NULL,	-- 0:annual, 1:monthly, 2:daily

	amount_base_depreciation_ht			double(24,8),
	amount_base_deductible_ht			double(24,8),
	total_amount_last_depreciation_ht	double(24,8),

	tms                                 timestamp       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_modif						integer
) ENGINE=innodb;
