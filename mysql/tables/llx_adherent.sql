-- ===================================================================
-- Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2006      Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- ===================================================================
--
-- statut
-- -1 : brouillon
--  0 : resilie
--  1 : valide

create table llx_adherent
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  nom              varchar(50),
  prenom           varchar(50),
  login            varchar(50) NOT NULL, -- login
  pass             varchar(50),          -- password
  fk_adherent_type smallint,
  morphy           enum('mor','phy') NOT NULL, -- personne morale / personne physique
  societe          varchar(50),
  adresse          text,
  cp               varchar(30),
  ville            varchar(50),
  pays             varchar(50),
  email            varchar(255),
  phone            varchar(30),
  phone_perso      varchar(30),
  phone_mobile     varchar(30),
  naiss            date,             -- date de naissance
  photo            varchar(255),     -- url vers photo
  statut           smallint NOT NULL DEFAULT 0,
  public           smallint NOT NULL DEFAULT 0, -- certain champ de la fiche sont ils public ou pas ?
  datefin          datetime,  -- date de fin de validité de la cotisation
  note             text,
  datevalid        datetime,  -- date de validation
  datec            datetime,  -- date de creation
  tms              timestamp, -- date de modification
  fk_user_author   integer NOT NULL,
  fk_user_mod      integer,
  fk_user_valid    integer, 
  UNIQUE INDEX(login)
)type=innodb;
