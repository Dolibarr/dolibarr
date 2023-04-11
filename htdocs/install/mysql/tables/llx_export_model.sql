-- ===================================================================
-- Copyright (C) 2001-2003	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
-- Copyright (C) 2007-2012	Regis Houssin			<regis.houssin@inodbox.com>
-- Copyright (C) 2011		Laurent Destailleur		<eldy@users.sourceforge.net>
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
--
-- ===================================================================

create table llx_export_model
(
  	rowid		integer AUTO_INCREMENT PRIMARY KEY,
  	entity      integer DEFAULT 0,      				-- by default on all entities for compatibility
	fk_user		integer DEFAULT 0 NOT NULL,
  	label		varchar(50) NOT NULL,
  	type		varchar(64) NOT NULL,
  	field		text NOT NULL,
  	filter		text
)ENGINE=innodb;
