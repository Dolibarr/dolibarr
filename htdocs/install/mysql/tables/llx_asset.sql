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
	rowid						integer			AUTO_INCREMENT PRIMARY KEY NOT NULL,
	entity						integer			DEFAULT 1 NOT NULL,  -- multi company id
	ref							varchar(128)	DEFAULT '(PROV)' NOT NULL,
	label						varchar(255)	NOT NULL,
	fk_asset_model				integer,

	reversal_amount_ht			double(24,8),
	acquisition_value_ht		double(24,8)	NOT NULL,
	recovered_vat				double(24,8),
	reversal_date				date,
	date_acquisition			date 			NOT NULL,
	date_start					date 			NOT NULL,
	qty							real			DEFAULT 1 NOT NULL,	-- qty asset
	acquisition_type			smallint		DEFAULT 0 NOT NULL,	-- new, occasion
	asset_type					smallint		DEFAULT 0 NOT NULL,	-- intangible, tangible, in progress, financial
	not_depreciated				integer(1)		DEFAULT 0,

	disposal_date				date,
	disposal_amount_ht			double(24,8),
	fk_disposal_type			integer,
	disposal_depreciated		integer(1)		DEFAULT 0,
	disposal_subject_to_vat		integer(1)		DEFAULT 0,

	note_public					text,
	note_private				text,
	date_creation				datetime		NOT NULL,
	tms							timestamp,
	fk_user_creat				integer			NOT NULL,
	fk_user_modif				integer,
	last_main_doc				varchar(255),
	import_key					varchar(14),
	model_pdf					varchar(255),
	status						smallint		NOT NULL
) ENGINE=innodb;
