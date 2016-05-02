-- ============================================================================
-- Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
-- Copyright (C) 2009      Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- ============================================================================

create table llx_product_lang
(
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  fk_product     integer      DEFAULT 0 NOT NULL,
  lang           varchar(5)   DEFAULT 0 NOT NULL,
  label          varchar(255) NOT NULL,
  description    text,
  note           text,
  import_key varchar(14) DEFAULT NULL
)ENGINE=innodb;
