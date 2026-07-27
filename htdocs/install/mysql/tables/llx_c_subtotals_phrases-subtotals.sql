-- ========================================================================
-- Copyright (C) 2026  Frédéric France         <frederic.france@free.fr>
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
-- Dictionary of predefined phrases usable as description of a subtotal title line
-- ========================================================================

create table llx_c_subtotals_phrases
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  entity      integer DEFAULT 1 NOT NULL,
  code        varchar(32),
  label       varchar(255),
  active      tinyint DEFAULT 1 NOT NULL
)ENGINE=innodb;
