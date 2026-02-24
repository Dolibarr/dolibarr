-- ============================================================================
-- Copyright (C) 2008-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
-- Copyright (C) 2011		Regis Houssin		<eldy@users.sourceforge.net>
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
-- Table used for defining fields positions for an elementtype
-- ============================================================================

create table llx_element_fields_positions
(
	rowid						      integer AUTO_INCREMENT PRIMARY KEY,
	entity						    integer DEFAULT 1 NOT NULL,
	elementtype						varchar(64) NOT NULL,
  fields_postitions     text,
  fk_user               integer NOT NULL
) ENGINE=innodb;
