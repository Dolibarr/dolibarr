-- ============================================================================
-- Copyright (C) 2004-2006 Laurent Destailleur <eldy@users.sourceforge.net>
-- Copyright (C) 2011-2016 Alexandre Spangaro	 <aspangaro@open-dsi.fr>
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
-- Table of chart of accounts
-- ============================================================================

create table llx_accounting_system
(
  rowid             integer         AUTO_INCREMENT PRIMARY KEY,
  fk_country		integer,
  pcg_version       varchar(32)     NOT NULL,
  label             varchar(128)    NOT NULL,
  active            smallint        DEFAULT 0
)ENGINE=innodb;
