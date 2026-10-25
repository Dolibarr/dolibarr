-- ============================================================================
-- Copyright (C) 2024		AWeerWolf
-- Copyright (C) 2026		Alexandre Spangaro	 <alexandre@inovea-conseil.com>
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
-- Table of line of transaction template for accounting
-- ============================================================================


CREATE TABLE llx_accounting_transaction_template_det (
	rowid					integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	fk_transaction_template	integer NOT NULL,
	general_account			varchar(32) NOT NULL,
	general_label			varchar(255) NOT NULL,
	subledger_account		varchar(32),
	subledger_label			varchar(255),
	operation_label			varchar(255),
	debit					double(24,8),
	credit					double(24,8)
) ENGINE=innodb;
