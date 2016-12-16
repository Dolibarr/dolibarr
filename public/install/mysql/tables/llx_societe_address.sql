-- ========================================================================
-- Copyright (C) 2000-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005-2013 Houssin Regis        <regis.houssin@capnetworks.com>
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

create table llx_societe_address
(
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  datec	             datetime,                            -- creation date
  tms                timestamp,                           -- modification date
  label              varchar(30),                         --
  fk_soc	         integer        DEFAULT 0,            --
  name               varchar(60),                         -- company name
  address            varchar(255),                        -- company adresse
  zip                varchar(10),                         -- zipcode
  town               varchar(50),                         -- town
  fk_pays            integer        DEFAULT 0,            --
  phone              varchar(20),                         -- phone number
  fax                varchar(20),                         -- fax number
  note               text,                                --
  fk_user_creat      integer,
  fk_user_modif      integer
)ENGINE=innodb;