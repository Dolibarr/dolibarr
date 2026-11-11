-- ============================================================================
-- Copyright (C) 2026		Alexandre Spangaro   	<alexandre@inovea-conseil.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
-- ============================================================================

CREATE TABLE llx_accounting_balance_snapshot (
	rowid              	integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
	entity             	integer DEFAULT 1 NOT NULL,
	fk_fiscalyear      	integer NOT NULL,
	account_number     	varchar(32) NOT NULL,
	account_label		varchar(255) NOT NULL,
	subledger_account  	varchar(32),
	subledger_label    	varchar(255),
	debit              	double(24,8) NOT NULL default 0,
	credit             	double(24,8) NOT NULL default 0,
	date_snapshot      	datetime,
	tms                	timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=innodb;
