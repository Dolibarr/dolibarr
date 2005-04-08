-- ========================================================================
-- Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

create table llx_telephonie_adsl_ligne (
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  fk_client          integer NOT NULL,
  fk_client_install  integer NOT NULL,
  fk_client_facture  integer NOT NULL,
  numero_ligne       varchar(20) NOT NULL,
  login              varchar(255),
  ip                 varchar(20),
  password           varchar(50),
  fk_fournisseur     integer,
  fk_type            integer NOT NULL,
  fk_commercial      integer NOT NULL,
  fk_user_creat      integer NOT NULL,
  statut             smallint DEFAULT -1,
  note               text

)type=innodb;
