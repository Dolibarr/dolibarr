-- ========================================================================
-- Copyright (C) 2025      Charlene Benke       <charlene@patas-monkey.com>
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
--
-- Table of events and actions (past and to do).
-- This is also the table to track events on other Dolibarr objects.
-- ========================================================================

create table llx_actioncomm_email
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  fk_actioncomm		integer,
  fk_c_email_type	integer,
  email				varchar(255)
)ENGINE=innodb;
