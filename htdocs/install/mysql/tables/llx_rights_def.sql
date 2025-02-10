-- ============================================================================
-- Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
-- Copyright (C) 2009-2024	Regis Houssin			<regis.houssin@inodbox.com>
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
-- ===========================================================================

create table llx_rights_def
(
  id				integer NOT NULL,
  entity			integer DEFAULT 1 NOT NULL,	-- Multicompany id
  libelle			varchar(255),
  module			varchar(64),
  module_origin		varchar(64),				-- if the permission is for a module but provided by another module, we add here the name of the module that provides the permission
  module_position	integer DEFAULT 0 NOT NULL,
  family_position	integer DEFAULT 0 NOT NULL,
  perms				varchar(50),
  subperms			varchar(50),
  type				varchar(1),					-- deprecated
  bydefault			tinyint DEFAULT 0,
  enabled			text NULL					-- Condition to show or hide
)ENGINE=innodb;
