-- ========================================================================
-- Copyright (C) 2013 Cédric Salvador <csalvador@gpcsolutions.fr>
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
--
-- Actions commerciales
-- ========================================================================

create table llx_links
(
  rowid             INTEGER AUTO_INCREMENT PRIMARY KEY,
  entity            INTEGER DEFAULT 1 NOT NULL,     -- multi company id
  datea             DATETIME NOT NULL,              -- date start
  url               VARCHAR(255) NOT NULL,          -- link url
  label             VARCHAR(255) NOT NULL,          -- link label
  objecttype        VARCHAR(255) NOT NULL,          -- object type in Dolibarr
  objectid          INTEGER NOT NULL
)ENGINE=innodb;
