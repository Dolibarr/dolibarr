-- ===========================================================================
-- Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

create table llx_projet
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc           integer,
  fk_statut        smallint NOT NULL,
  datec            date,         -- date creation project
  tms              timestamp,
  dateo            date,         -- date start project
  ref              varchar(50),
  entity           integer DEFAULT 1 NOT NULL, -- multi company id
  title            varchar(255),
  fk_user_resp     integer,      -- responsable du projet
  fk_user_creat    integer,      -- createur du projet
  note             text
)type=innodb;
