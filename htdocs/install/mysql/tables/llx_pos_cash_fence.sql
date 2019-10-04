-- Copyright (C) 2018 Andreu Bisquerra     <jove@bisquerra.com>
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


CREATE TABLE llx_pos_cash_fence(
	rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
	entity INTEGER DEFAULT 1 NOT NULL,
	ref VARCHAR(64),
	label VARCHAR(255),
	opening double(24,8) default 0,
	cash double(24,8) default 0,
	card double(24,8) default 0,
	cheque double(24,8) default 0,
	status INTEGER,
	date_creation DATETIME NOT NULL,
	date_valid DATETIME,
	day_close INTEGER,
	month_close INTEGER,
	year_close INTEGER,
	posmodule VARCHAR(30),
	posnumber VARCHAR(30),
	fk_user_creat integer,
	fk_user_valid integer,
	tms TIMESTAMP,
	import_key VARCHAR(14)
) ENGINE=innodb;
