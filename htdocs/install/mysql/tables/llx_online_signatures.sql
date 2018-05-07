-- ============================================================================
-- Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
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
-- ============================================================================

create table llx_onlinesignature
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  entity                    integer DEFAULT 1 NOT NULL,
  object_type               varchar(32) NOT NULL,
  object_id					integer NOT NULL,
  datec                     datetime NOT NULL,
  tms                       timestamp,
  name						varchar(255) NOT NULL,
  ip						varchar(128),
  pathoffile				varchar(255)
)ENGINE=innodb;
