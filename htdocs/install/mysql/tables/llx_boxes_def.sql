-- ============================================================================
-- Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2010      Laurent Destailleur  <eldy@users.sourceforge.net>
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

-- Table 
create table llx_boxes_def
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  file        varchar(200) NOT NULL,        -- Do not increase this as file+note must be small to allow index
  entity      integer DEFAULT 1 NOT NULL,	-- multi company id
  fk_user     integer DEFAULT 0 NOT NULL,	-- if widget is privte to one user
  tms         timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,  
  note        varchar(130)                  -- Do not increase this as file+note must be small to allow index
)ENGINE=innodb;
