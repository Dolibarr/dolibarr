-- ============================================================================
-- Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2009 Regis Houssin        <regis.houssin@inodbox.com>
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
-- ===========================================================================

create table llx_rights_def
(
  id            integer NOT NULL,
  libelle       varchar(255),
  module        varchar(64),
  module_position integer NOT NULL DEFAULT 0,
  family_position integer NOT NULL DEFAULT 0,
  entity        integer DEFAULT 1 NOT NULL,
  perms         varchar(50),
  subperms      varchar(50),
  type          varchar(1),
  bydefault     tinyint DEFAULT 0
)ENGINE=innodb;

-- 
-- List of codes for the field entity
--
-- 1 : first company user
-- 2 : second company user
-- 3 : etc...
--
