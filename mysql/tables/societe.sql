-- ========================================================================
-- Copyright (C) 2000-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
--
-- $Id$
-- $Source$
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
-- SGBD : Mysql 3.23
--
-- ========================================================================
create table societe
(
  idp            integer AUTO_INCREMENT PRIMARY KEY,
  id             varchar(32),                         -- private id
  active         smallint       default 0,            --
  parent         integer        default 0,            --
  tms            timestamp,
  datec	         datetime,                            -- creation date
  datea	         datetime,                            -- activation date
  nom            varchar(60),                         -- company name
  address        varchar(255),                        -- company adresse
  cp             varchar(10),                         -- zipcode
  ville          varchar(50),                         -- town
  fk_pays        integer        default 0,            --
  tel            varchar(20),                         -- phone number
  fax            varchar(20),                         -- fax number
  url            varchar(255),                        --
  fk_secteur     integer        default 0,            --
  fk_effectif    integer        default 0,            --
  fk_typent      integer        default 0,            --
  siren	         varchar(9),                          --
  description    text,                                --
  fk_stcomm      smallint       default 0,            -- commercial statut
  note           text,                                --
  services       integer        default 0,            --
  prefix_comm    varchar(5),                          -- prefix commercial
  client         smallint       default 0,            -- client oui/non
  fournisseur    smallint       default 0,            -- fournisseur oui/non

  UNIQUE INDEX(prefix_comm)
);
