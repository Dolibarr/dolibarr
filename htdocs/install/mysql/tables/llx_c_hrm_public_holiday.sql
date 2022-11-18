-- ========================================================================
-- Copyright (C) 2019	Laurent Destailleur		<eldy@users.sourceforge.net>
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
-- ========================================================================

create table llx_c_hrm_public_holiday
(
  id               integer AUTO_INCREMENT PRIMARY KEY,
  entity           integer	DEFAULT 0 NOT NULL,   -- multi company id, 0 = all
  fk_country       integer,
  fk_departement   integer,
  code             varchar(62),
  dayrule          varchar(64) DEFAULT '',        -- 'easter', 'eastermonday', ...
  day              integer,
  month            integer,
  year             integer,                       -- 0 for all years
  active           integer DEFAULT 1,
  import_key       varchar(14)
)ENGINE=innodb;
