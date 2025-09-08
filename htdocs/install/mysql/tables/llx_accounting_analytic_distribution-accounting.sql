-- ============================================================================
-- Copyright (C) 2025		Alexandre Spangaro  <alexandre@inovea-conseil.com>
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
-- Table of 'analytic distribution' for accountancy module
-- ============================================================================

CREATE TABLE llx_accounting_analytic_distribution (
	rowid				integer			AUTO_INCREMENT PRIMARY KEY,
	fk_source_line		integer			NOT NULL,		-- id de la ligne source (facture, écriture, etc.)
	sourcetype			varchar(32)		NOT NULL,		-- ex: 'facturedet', 'accounting_line'
	fk_analytic_account integer			NOT NULL,
	percentage			real			DEFAULT 100,	-- ex: 50.0 pour 50%
	amount				double(24,8),					-- optional, if you prefer to work on the amount
	entity				integer			DEFAULT 1,
	datec               datetime,
	tms                 timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_author      integer         NOT NULL,
	fk_user_modif       integer,
	import_key          varchar(14)
) ENGINE=innodb;
