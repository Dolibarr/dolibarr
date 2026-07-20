-- ============================================================================
-- Copyright (C) 2025		Alexandre Spangaro		<alexandre@inovea-conseil.com>
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

CREATE TABLE llx_accounting_bookkeeping_piece
(
  rowid                 integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
  entity                integer DEFAULT 1 NOT NULL,
  ref             		varchar(128),
  tms					timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  datec					datetime,
  datep					date NOT NULL,
  statut				smallint DEFAULT 0,

  note_private			text,
  note_public			text,

  fk_user_author		integer,
  fk_user_modif			integer,
  fk_user_valid			integer,
  fk_user_closing		integer,

  import_key            varchar(14),
  extraparams           varchar(255)
) ENGINE=innodb;
