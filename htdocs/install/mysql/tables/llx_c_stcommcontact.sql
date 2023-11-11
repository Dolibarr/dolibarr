-- ========================================================================
-- Copyright (C) 2020 	   Open-Dsi  <support@open-dsi.fr>
-- Copyright (C) 2022 	   Juanjo Menent        <jmenent@2byte.es>
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
-- ========================================================================

create table llx_c_stcommcontact
(
  id       integer      PRIMARY KEY,
  code     varchar(12)  NOT NULL,
  libelle  varchar(128),
  picto    varchar(128),
  active   tinyint default 1  NOT NULL
)ENGINE=innodb;

