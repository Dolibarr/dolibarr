-- ============================================================================
-- Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2009 Regis Houssin        <regis@dolibarr.fr>
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

create table llx_rights_def
(
  id            integer,
  libelle       varchar(255),
  module        varchar(12),
  entity        integer DEFAULT 1 NOT NULL,
  perms         varchar(50),
  subperms      varchar(50),
  type          varchar(1),
  bydefault     tinyint DEFAULT 0
)type=innodb;

-- 
-- List of codes for the field entity
--
-- 1 : first company user
-- 2 : second company user
-- 3 : etc...
--