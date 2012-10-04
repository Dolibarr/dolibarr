-- ============================================================================
-- Copyright (C) 2004-2006 Laurent Destailleur <eldy@users.sourceforge.net>
-- Copyright (C) 2011-2012 Alexandre Spangaro	 <alexandre.spangaro@gmail.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
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
-- Table of "Plan de comptes" for accountancy expert module
-- ============================================================================

create table llx_accountingsystem
(
  rowid             integer         AUTO_INCREMENT PRIMARY KEY,
  pcg_version       varchar(12)     NOT NULL,
  fk_pays           integer         NOT NULL,
  label             varchar(128)    NOT NULL,
  active            smallint        DEFAULT 0
)ENGINE=innodb;
