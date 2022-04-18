-- ===================================================================
-- Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
-- ===================================================================

create table llx_subscription
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  datec           datetime,
  fk_adherent     integer,
  fk_type         integer,
  dateadh         datetime,
  datef           datetime,
  subscription    double(24,8),
  fk_bank         integer DEFAULT NULL,
  fk_user_creat   integer DEFAULT NULL,
  fk_user_valid   integer DEFAULT NULL,
  note            text
)ENGINE=innodb;
