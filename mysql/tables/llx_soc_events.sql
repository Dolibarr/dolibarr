-- ========================================================================
-- Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
-- $Source$
-- ========================================================================

create table llx_soc_events
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,  -- public id
  fk_soc        int          NOT NULL,            --
  dateb	        datetime    NOT NULL,            -- begin date
  datee	        datetime    NOT NULL,            -- end date
  title         varchar(100) NOT NULL,
  url           varchar(255),
  description   text
);
