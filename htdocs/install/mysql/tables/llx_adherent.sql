-- ===================================================================
-- Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2009      Regis Houssin        <regis.houssin@inodbox.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
-- ===================================================================
--
-- state / statut
-- -2 : excluded / exclu 
-- -1 : draft / brouillon
--  0 : canceled / resilie
--  1 : valid / valide
--

create table llx_adherent
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  ref              varchar(30) NOT NULL,        -- member reference number
  entity           integer DEFAULT 1 NOT NULL,  -- multi company id
  ref_ext          varchar(128),                -- reference into an external system (not used by dolibarr)

  gender           varchar(10),
  civility         varchar(6),
  lastname         varchar(50),
  firstname        varchar(50),
  login            varchar(50),                 -- login
  pass             varchar(50),                 -- password
  pass_crypted     varchar(128),
  fk_adherent_type integer NOT NULL,
  morphy           varchar(3) NOT NULL,         -- EN: legal entity / natural person  FR: personne morale / personne physique
  societe          varchar(128),			          -- company name (should be same length than societe.name). No more used.
  fk_soc           integer NULL,		            -- Link to third party linked to member
  address          text,
  zip              varchar(30),
  town             varchar(50),
  state_id         integer,
  country          integer,
  email            varchar(255),
  url              varchar(255) NULL,

  socialnetworks   text DEFAULT NULL,           -- json with socialnetworks
  --skype            varchar(255),                -- deprecated
  --twitter          varchar(255),                -- deprecated
  --facebook         varchar(255),                -- deprecated
  --linkedin         varchar(255),                -- deprecated
  --instagram        varchar(255),                -- deprecated
  --snapchat         varchar(255),                -- deprecated
  --googleplus       varchar(255),                -- deprecated
  --youtube          varchar(255),                -- deprecated
  --whatsapp         varchar(255),                -- deprecated

  phone            varchar(30),
  phone_perso      varchar(30),
  phone_mobile     varchar(30),
  birth            date,                          -- birthday
  photo            varchar(255),                  -- filename or url of photo
  statut           smallint NOT NULL DEFAULT 0,
  public           smallint NOT NULL DEFAULT 0,   -- certain champ de la fiche sont ils public ou pas ?
  datefin          datetime,                      -- end date of validity of the contribution / date de fin de validite de la cotisation
  note_private     text DEFAULT NULL,
  note_public      text DEFAULT NULL,
  model_pdf		     varchar(255),
  datevalid        datetime,                      -- date of validation
  datec            datetime,                      -- date of creation
  tms              timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- last modification date
  fk_user_author   integer,                       -- can be null because member can be create by a guest
  fk_user_mod      integer,
  fk_user_valid    integer,
  canvas           varchar(32),                   -- type of canvas if used (null by default)
  import_key       varchar(14)                    -- Import key
)ENGINE=innodb;
