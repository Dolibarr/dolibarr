-- ===================================================================
-- Copyright (C) 2000-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
--
-- $Id$
-- $Source$
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General [public] License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General [public] License for more details.
--
-- You should have received a copy of the GNU General [public] License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- ===================================================================

create table llx_compta
(
  rowid             int IDENTITY PRIMARY KEY,
  datec             datetime,
  datev             datetime,           -- date de valeur
  amount            real DEFAULT 0 NOT NULL ,
  label             varchar(255),
  fk_compta_account int,
  fk_user_author    int,
  fk_user_valid     int,
  valid             tinyint DEFAULT 0,
  note              text

);
