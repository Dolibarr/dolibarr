-- ============================================================================
-- Copyright (C) 2005	Laurent Destailleur	<eldy@users.sourceforge.net>
-- Copyright (C) 2017	Regis Houssin		<regis.houssin@inodbox.com>
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
-- ===========================================================================

create table llx_usergroup_rights
(
  rowid			integer AUTO_INCREMENT PRIMARY KEY,
  entity		integer DEFAULT 1 NOT NULL, -- multi company id
  fk_usergroup	integer NOT NULL,
  fk_id			integer NOT NULL

)ENGINE=innodb;

