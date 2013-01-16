-- ============================================================================
-- Copyright (C) 2004-2006 Laurent Destailleur <eldy@users.sourceforge.net>
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
-- Table of "accounts" for accountancy expert module
-- ============================================================================

create table llx_accountingaccount
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_pcg_version  varchar(12)  NOT NULL,
  pcg_type        varchar(20)  NOT NULL,
  pcg_subtype     varchar(20)  NOT NULL,
  account_number  varchar(20)  NOT NULL,
  account_parent  varchar(20),
  label           varchar(128) NOT NULL,
  active     	  tinyint DEFAULT 1  NOT NULL
)ENGINE=innodb;
