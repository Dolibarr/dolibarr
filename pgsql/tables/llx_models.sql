-- Generated from dolibarr_mysql2pgsql
-- (c) 2004, PostgreSQL Inc.
-- (c) 2005, Laurent Destailleur.

-- ============================================================================
-- Copyright (C) 2005 Laurent Destailleur <eldy@users.sourceforge.net>
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

create table llx_models
(
  rowid SERIAL PRIMARY KEY,
  "tms"             timestamp,
  "type"            varchar(32),                  -- Fonction destinee au modele
  "public"          smallint DEFAULT 1 NOT NULL,  -- Model publique ou privee
  "fk_user"         integer,                      -- Id utilisateur si privee, sinon null
  "title"           varchar(128),                 -- Titre modele
  "content"         text                          -- Texte du modele
);
