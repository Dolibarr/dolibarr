-- Generated from dolibarr_mysql2pgsql
-- (c) 2004, PostgreSQL Inc.
-- (c) 2005, Laurent Destailleur.

-- ============================================================================
-- Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
-- ============================================================================


create table llx_livre
(
  rowid SERIAL PRIMARY KEY,
  "oscid"           integer NOT NULL,
  "tms"             timestamp,
  "status"          tinyint,
  "date_ajout"      datetime,
  "ref"             varchar(12),
  "title"           varchar(64),
  "annee"           int2,
  "description"     text,
  "prix"            decimal(15,4),
  "fk_editeur"      integer,
  "fk_user_author"  integer,
  "frais_de_port"   tinyint DEFAULT 1
);

