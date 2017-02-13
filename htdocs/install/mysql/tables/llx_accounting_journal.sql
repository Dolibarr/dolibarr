-- ============================================================================
-- Copyright (C) 2016	 Alexandre Spangaro	 <aspangaro.dolibarr@gmail.com>
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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- Table of journals for accountancy
-- ============================================================================

create table llx_accounting_journal
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  code       		varchar(32) NOT NULL,
  label             varchar(128) NOT NULL,
  nature			smallint DEFAULT 0 NOT NULL,			-- type of journals (Sale / purchase / bank / various operations)
  active            smallint DEFAULT 0
)ENGINE=innodb;
