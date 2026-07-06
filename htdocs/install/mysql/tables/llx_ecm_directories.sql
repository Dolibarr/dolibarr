-- ===================================================================
-- Copyright (C) 2008-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
-- Copyright (C) 2009-2012	Regis Houssin		<regis.houssin@inodbox.com>
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
-- ===================================================================

-- DROP TABLE llx_ecm_directories;

CREATE TABLE llx_ecm_directories
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  label				varchar(64) NOT NULL,
  entity			integer DEFAULT 1 NOT NULL,		-- multi company id
  fk_parent			integer,
  description		varchar(255) NOT NULL,
  cachenbofdoc		integer NOT NULL DEFAULT 0,
  fullpath    		varchar(750),
  extraparams		varchar(255),					-- for stock other parameters with json format
  date_c			datetime,
  tms				timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_c			integer,
  fk_user_m			integer,
  note_private		text,
  note_public		text,
  acl				text
) ENGINE=innodb;
