-- ============================================================================
-- Copyright (C) 2010-2021 Regis Houssin  <regis.houssin@inodbox.com>
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
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- ===========================================================================

create table llx_user_perentity
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  entity			integer DEFAULT 1 NOT NULL,	-- multi company id
  fk_user			integer NOT NULL,
  office_phone		varchar(20) DEFAULT NULL,
  office_fax		varchar(20) DEFAULT NULL,
  user_mobile		varchar(20) DEFAULT NULL,
  personal_mobile	varchar(20) DEFAULT NULL,
  email				varchar(255) DEFAULT NULL,
  personal_email	varchar(255) DEFAULT NULL,
  signature			text DEFAULT NULL
  
) ENGINE=innodb;