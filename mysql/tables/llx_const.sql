-- ============================================================================
-- Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2008      Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2009      Regis Houssin        <regis@dolibarr.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
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
-- $Id$
-- ===========================================================================

--
-- Table for constants used to store Dolibarr setup
--

create table llx_const
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  name        varchar(255),
  value       text, -- max 65535 caracteres
  type        varchar(6),
  visible     tinyint DEFAULT 1 NOT NULL,
  note        text,
  tms         timestamp,
  entity      integer DEFAULT 1 NOT NULL	-- multi company id
) type=innodb;

-- 
-- List of codes for the field entity
--
-- 0 : common constant
-- 1 : first company constant
-- 2 : second company constant
-- 3 : etc...
--