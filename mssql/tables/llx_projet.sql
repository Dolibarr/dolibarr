-- ===========================================================================
-- Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
-- $Id$
-- $Source$
-- 
-- ===========================================================================

create table llx_projet
(
  rowid            int IDENTITY PRIMARY KEY,
  fk_soc           int  NOT NULL,
  fk_statut        smallint NOT NULL,
  tms              timestamp,
  dateo            datetime,         -- date d'ouverture du projet
  ref              varchar(50),
  title            varchar(255),
  fk_user_resp     int,      -- responsable du projet
  fk_user_creat    int,      -- createur du projet
  note             text,
);

CREATE UNIQUE INDEX ref ON llx_projet(ref)
