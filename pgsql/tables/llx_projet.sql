-- ===========================================================================
-- Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2002-2003 Éric Seigne <erics@rycks.com>
-- Copyright (C) 2004 Benoit Mortier <benoit.mortier@opensides.be>
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
--
-- ===========================================================================

create table llx_projet
(
  rowid            SERIAL PRIMARY KEY,
  fk_soc           integer  NOT NULL,
  fk_statut        smallint NOT NULL,
  tms              timestamp,
  dateo            date,  -- date d'ouverture du projet
  ref              varchar(50),
  title            varchar(255),
  fk_user_resp     integer,   -- responsable du projet
  fk_user_creat    integer,   -- createur du projet
  note             text
);

create unique index llx_projet_ref on llx_projet(ref);
