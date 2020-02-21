-- ===================================================================
-- Copyright (C) 2001-2019 Laurent Destailleur <eldy@users.sourceforge.net>
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
-- Table with templates of emails
-- ===================================================================

create table llx_c_email_senderprofile
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  entity		  integer DEFAULT 1 NOT NULL,	  -- multi company id
  private         smallint DEFAULT 0 NOT NULL,    -- Template public (0) or private (id of user)
  date_creation   datetime,
  tms             timestamp,
  label           varchar(255),					  -- Label of predefined email
  email           varchar(255) NOT NULL,		  -- Email
  signature		  text,                           -- Predefined signature
  position        smallint DEFAULT 0,		      -- Position
  active          tinyint DEFAULT 1 NOT NULL
)ENGINE=innodb;
