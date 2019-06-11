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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
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
  ref_ext          varchar(128),                -- reference into an external system (not used by dolibarr)

  gender           varchar(10),
  civility         varchar(6),
  lastname         varchar(50),
  firstname        varchar(50),
  login            varchar(50),          -- login
  pass             varchar(50),          -- password
  pass_crypted     varchar(128),
  fk_adherent_type integer NOT NULL,
  morphy           varchar(3) NOT NULL, -- personne morale / personne physique
  societe          varchar(128),			-- company name (should be same lenght than societe.name)
  fk_soc           integer NULL,		-- Link to third party linked to member
  address          text,
  zip              varchar(30),
  town             varchar(50),
  state_id         integer,
  country          integer,
  email            varchar(255),

  skype            varchar(255),
  twitter          varchar(255),                        		--
  facebook         varchar(255),                        		--
  linkedin         varchar(255),                        		--
  instagram        varchar(255),                        		--
  snapchat         varchar(255),                        		--
  googleplus       varchar(255),                        		--
  youtube          varchar(255),                        		--
  whatsapp         varchar(255),                        		--

  phone            varchar(30),
  phone_perso      varchar(30),
  phone_mobile     varchar(30),
  birth            date,             -- birthday
  photo            varchar(255),     -- filename or url of photo
  statut           smallint NOT NULL DEFAULT 0,
  public           smallint NOT NULL DEFAULT 0, -- certain champ de la fiche sont ils public ou pas ?
  datefin          datetime,  -- date de fin de validite de la cotisation
  note_private     text DEFAULT NULL,
  note_public      text DEFAULT NULL,
  model_pdf		   varchar(255),
  datevalid        datetime,  -- date de validation
  datec            datetime,  -- date de creation
  tms              timestamp, -- date de modification
  fk_user_author   integer,   -- can be null because member can be create by a guest
  fk_user_mod      integer,
  fk_user_valid    integer,
  canvas		   varchar(32),			                        -- type of canvas if used (null by default)
  import_key       varchar(14)                  -- Import key
)ENGINE=innodb;
