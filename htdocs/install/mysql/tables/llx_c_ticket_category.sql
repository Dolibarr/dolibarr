-- Copyright (C) 2013-2018  Jean-François FERRY <hello@librethic.io>
-- Copyright (C) 2020-2021  Laurent Destailleur <eldy@users.sourceforge.net>
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
-- Table with the custom category tree for the category of a ticket
-- 

create table llx_c_ticket_category
(
  rowid			 integer AUTO_INCREMENT PRIMARY KEY,
  entity		 integer DEFAULT 1,
  code			 varchar(32) NOT NULL,			-- Example: TIGRP-COMMERCIAL, TIGRP-TECHNICALISSUE, ...
  label			 varchar(128) NOT NULL,
  public         integer DEFAULT 0,
  use_default	 integer DEFAULT 1,
  fk_parent      integer DEFAULT 0 NOT NULL,	-- Parent group
  force_severity varchar(32) NULL,				-- To force the severity if we choosed this category
  description	 varchar(255),					-- A long description of ticket
  pos			 integer DEFAULT 0 NOT NULL,
  active		 integer DEFAULT 1
)ENGINE=innodb;
