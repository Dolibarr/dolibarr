-- ========================================================================
-- Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
-- ========================================================================
--
--
create table llx_telephonie_service (
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  ref              varchar(25) NOT NULL,

  libelle          varchar(255),
  libelle_facture  varchar(255),
  montant          real NOT NULL,
  statut           smallint DEFAULT 0 NOT NULL,

  fk_user_creat    integer NOT NULL,
  date_creat       datetime NOT NULL,
  fk_user_modif    integer,
  date_modif       datetime
)type=innodb;


