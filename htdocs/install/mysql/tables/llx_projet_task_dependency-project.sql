-- ============================================================================
-- Copyright (C) 2026 Dolibarr contributors
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

create table llx_projet_task_dependency
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_task         integer NOT NULL,          -- the task that has a predecessor
  fk_task_depend  integer NOT NULL,          -- the predecessor task (must be finished/started first)
  type            varchar(2) DEFAULT 'FS' NOT NULL,  -- dependency type: FS, SS, FF, SF
  entity          integer DEFAULT 1 NOT NULL,
  datec           datetime
)ENGINE=innodb;
