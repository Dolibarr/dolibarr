-- ========================================================================
-- Copyright (C) 2015   Peter Fontaine    <contact@peterfontaine.fr>
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

CREATE TABLE llx_dashboardlines (
  rowid          integer      AUTO_INCREMENT PRIMARY KEY,
  module         varchar(255) NOT NULL,
  class_file     varchar(255) NOT NULL,
  class_name     varchar(255) NOT NULL,
  class_func     varchar(255) NOT NULL,
  extra_param    varchar(255) DEFAULT NULL,
  allow_external smallint     DEFAULT 0 NOT NULL,
  perm           varchar(255) DEFAULT NULL,
  entity         integer      DEFAULT 1 NOT NULL
)ENGINE=innodb;
