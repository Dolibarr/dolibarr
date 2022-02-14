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

CREATE TABLE llx_asset_depreciation(
    rowid                           integer         AUTO_INCREMENT PRIMARY KEY NOT NULL,

    fk_asset                        integer         NOT NULL,
    depreciation_mode               varchar(255)	NOT NULL,	-- (economic, fiscal or other)

    ref                             varchar(255)	NOT NULL,
    depreciation_date               datetime		NOT NULL,
    depreciation_ht                 double(24,8)	NOT NULL,
    cumulative_depreciation_ht      double(24,8)	NOT NULL,

    accountancy_code_debit          varchar(32),
    accountancy_code_credit         varchar(32),

	tms                             timestamp       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_modif                   integer
) ENGINE=innodb;
