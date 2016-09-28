-- ===================================================================
-- Copyright (C) 2016      Ion Agorria          <ion@agorria.com>
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
-- ===================================================================

CREATE TABLE llx_resource_log
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_resource     integer NOT NULL,
  fk_user         integer NOT NULL,
  booker_id       integer,           -- id of booker
  booker_type     varchar(64),       -- booker type
  date_creation   datetime NOT NULL,
  date_start      datetime NOT NULL,
  date_end        datetime NOT NULL,
  status          integer NOT NULL,
  action          integer NOT NULL
)ENGINE=innodb;