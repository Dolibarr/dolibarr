-- ===================================================================
-- Copyright (C) 2004	   Benoit Mortiero <benoit.mortier@opensides.be>
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
-- ===================================================================

-- statut
-- 0 : non adherent
-- 1 : adherent

create table llx_adherent
(
  rowid            SERIAL PRIMARY KEY,
  tms              timestamp,
  statut           smallint NOT NULL DEFAULT 0,
  public           smallint NOT NULL DEFAULT 0, -- certain champ de la fiche sont ils public ou pas ?
  fk_adherent_type smallint,
  morphy           CHAR(3) CHECK (morphy ('mor','phy')) NOT NULL, -- personne morale / personne physique
  datevalid        timestamp,   -- date de validation
  datec            timestamp,  -- date de creation
  prenom           varchar(50),
  nom              varchar(50),
  societe          varchar(50),
  adresse          text,
  cp               varchar(30),
  ville            varchar(50),
  pays             varchar(50),
  email            varchar(255),
  login            varchar(50) NOT NULL,      -- login utilise pour editer sa fiche
  pass             varchar(50),      -- pass utilise pour editer sa fiche
  naiss            date,             -- date de naissance
  photo            varchar(255),     -- url vers la photo de l'adherent
  fk_user_author   integer NOT NULL,
  fk_user_mod      integer NOT NULL,
  fk_user_valid    integer NOT NULL,
  datefin          timestamp NOT NULL, -- date de fin de validité de la cotisation
  note             text
);

