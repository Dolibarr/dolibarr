-- ============================================================================
-- Copyright (C) 2008-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
-- Copyright (C) 2011		Regis Houssin		<eldy@users.sourceforge.net>
-- Copyright (C) 2012		Philippe Grand		<philippe.grand@atoo-net.com>
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
-- ============================================================================
-- Table used for multi-user event
-- ============================================================================

create table llx_event_element
(
  rowid           	integer AUTO_INCREMENT PRIMARY KEY,  
  fk_source			integer NOT NULL,
  fk_target			integer NOT NULL,
  targettype		varchar(32) NOT NULL
) ENGINE=innodb;

