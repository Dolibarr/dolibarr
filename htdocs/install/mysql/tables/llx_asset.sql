-- Copyright (C) 2018      Alexandre Spangaro   <aspangaro@open-dsi.fr>
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


CREATE TABLE llx_asset(
	rowid                                                   integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	ref                                                     varchar(128) NOT NULL,
	entity                                                  integer DEFAULT 1 NOT NULL,
	label                                                   varchar(255),
	amount_ht                                               double(24,8) DEFAULT 0,
	amount_vat                                              double(24,8) DEFAULT 0,
	amount_base_depreciation_ht                             double(24,8) DEFAULT 0,
	amount_base_deductible_ht                               double(24,8) DEFAULT 0,
	fk_asset_type                                           integer NOT NULL,

	type_asset				                                tinyint     DEFAULT 0  NOT NULL,    -- type of asset
	type_asset_acquisition	                                tinyint     DEFAULT 0  NOT NULL,    -- type of asset
    type_asset_economical                                   tinyint     DEFAULT 0  NOT NULL,    -- economical type of asset
    duration                                                tinyint     DEFAULT 0  NOT NULL,

    total_ht_last_depreciation                              double(24,8) DEFAULT 0,

    accountancy_code_asset                                  varchar(32),
    accountancy_code_depreciation_asset                     varchar(32),
    accountancy_code_depreciation_expense                   varchar(32),
    accountancy_code_value_asset_sold                       varchar(32),
    accountancy_code_receivable_on_assignment               varchar(32),
    accountancy_code_proceeds_from_sales                    varchar(32),
    accountancy_code_vat_collected                          varchar(32),
    accountancy_code_vat_deductible                         varchar(32),

    accelerated_depreciation                                tinyint     DEFAULT 0  NOT NULL,
    accountancy_code_accelerated_depreciation               varchar(32),
    accountancy_code_endowment_accelerated_depreciation     varchar(32),
    accountancy_code_provision_accelerated_depreciation     varchar(32),

	description                                             text,
	note_public                                             text,
	note_private                                            text,
	date_creation                                           datetime NOT NULL,
	tms                                                     timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_creat                                           integer NOT NULL,
	fk_user_modif                                           integer,
	import_key                                              varchar(14),
	status                                                  integer NOT NULL
) ENGINE=innodb;
