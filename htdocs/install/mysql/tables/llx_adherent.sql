-- ===================================================================
-- Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2009      Regis Houssin        <regis@dolibarr.fr>
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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- $Id: llx_adherent.sql,v 1.12 2011/08/03 01:25:27 eldy Exp $
-- ===================================================================
--
-- statut
-- -1 : brouillon
--  0 : resilie
--  1 : valide

create table llx_adherent
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  entity           integer DEFAULT 1 NOT NULL,	-- multi company id
  ref_ext          varchar(30),                 -- reference into an external system (not used by dolibarr)

  civilite         varchar(6),
  nom              varchar(50),
  prenom           varchar(50),
  login            varchar(50),          -- login
  pass             varchar(50),          -- password
  fk_adherent_type integer NOT NULL,
  morphy           varchar(3) NOT NULL, -- personne morale / personne physique
  societe          varchar(50),
  fk_soc           integer NULL,		-- Link to third party linked to member
  adresse          text,
  cp               varchar(30),
  ville            varchar(50),
  fk_departement   integer,
  pays             integer,
  email            varchar(255),
  phone            varchar(30),
  phone_perso      varchar(30),
  phone_mobile     varchar(30),
  naiss            date,             -- date de naissance
  photo            varchar(255),     -- filename or url of photo
  statut           smallint NOT NULL DEFAULT 0,
  public           smallint NOT NULL DEFAULT 0, -- certain champ de la fiche sont ils public ou pas ?
  datefin          datetime,  -- date de fin de validite de la cotisation
  note             text,
  datevalid        datetime,  -- date de validation
  datec            datetime,  -- date de creation
  tms              timestamp, -- date de modification
  fk_user_author   integer,   -- can be null because member can be create by a guest
  fk_user_mod      integer,
  fk_user_valid    integer,
  import_key       varchar(14)                  -- Import key
)ENGINE=innodb;
