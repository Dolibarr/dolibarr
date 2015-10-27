-- ============================================================================
-- Copyright (C) 2013	Laurent Destailleur	<eldy@users.sourceforge.net>
-- Copyright (C) 2013	Florian Henry		<florian.henry@open-concept.pro>
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
-- ============================================================================
-- Table used for relations between an action event and a resource (in most cases
-- a 'user', but can also be a 'resource' like a room, or a hardware)
-- ============================================================================

create table llx_actioncomm_resources
(
  rowid           	integer AUTO_INCREMENT PRIMARY KEY,  
  fk_actioncomm		integer NOT NULL,			-- Id into llx_actioncomm
  element_type		varchar(50) NOT NULL,		-- Type of resource ('user', 'resource')
  fk_element		integer NOT NULL,			-- Id into table llx_user or llx_resource
  answer_status		varchar(50) NULL,
  mandatory			smallint,
  transparency		smallint default 1	    -- Used to say if event is 1=OPAQUE=busy or 0=TRANSPARENT
) ENGINE=innodb;
