-- ============================================================================
-- Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2008-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2009      Regis Houssin        <regis.houssin@inodbox.com>
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

--
-- Table for constants used to store Dolibarr setup
--

create table llx_const
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  name        varchar(180) NOT NULL,
  entity      integer DEFAULT 1 NOT NULL,	-- multi company id
  value       text NOT NULL, 				-- max 65535 caracteres
  type        varchar(64) DEFAULT 'string', -- null or 'encrypted' if param has been encrypted 
  visible     tinyint DEFAULT 1 NOT NULL,
  note        text,
  tms         timestamp
) ENGINE=innodb;

-- 
-- List of codes for the field entity
--
-- 0 : common constant
-- 1 : first company constant
-- 2 : second company constant
-- 3 : etc...
--
