-- ===================================================================
-- Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2014 Juanjo Menent		   <jmenent@2byte.es>
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
-- Table of notification done
-- ===================================================================

create table llx_notify
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  tms             timestamp,
  daten           datetime,           -- date de la notification
  fk_action       integer NOT NULL,
  fk_soc          integer NULL,
  fk_contact      integer NULL,
  fk_user         integer NULL,
  type            varchar(16) DEFAULT 'email',
  type_target     varchar(16) NULL,		-- What type of target notification was sent to ? 'tocontactid', 'touserid', 'tofixedemail'
  objet_type      varchar(24) NOT NULL,
  objet_id        integer NOT NULL,
  email           varchar(255)
)ENGINE=innodb;
