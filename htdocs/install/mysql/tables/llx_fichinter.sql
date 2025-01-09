-- ===================================================================
-- Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
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

create table llx_fichinter
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc			integer NOT NULL,
  fk_projet			integer DEFAULT 0,          -- projet auquel est rattache la fiche
  fk_contrat		integer DEFAULT 0,          -- contrat auquel est rattache la fiche
  ref				varchar(30) NOT NULL,       -- number
  ref_ext			varchar(255),
  ref_client		varchar(255),				-- customer intervention number
  entity			integer DEFAULT 1 NOT NULL, -- multi company id
  tms				timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  datec				datetime,                   -- date of creation
  date_valid		datetime,                   -- date of validation
  datei				date,						-- date of delivery of the intervention receipt
  fk_user_author	integer,					-- user making creation
  fk_user_modif     integer,                    -- user making last change
  fk_user_valid		integer,                    -- user validating record
  fk_statut			smallint  DEFAULT 0,
  dateo				date,						-- date start intervention
  datee				date,						-- date end intervention (diff with datet ?)
  datet				date,						-- date end intervention (diff with datee ?)
  duree				real,                       -- duration total of  intervention
  description		text,

  signed_status     smallint DEFAULT NULL,      -- signed status NULL, 0 or 1
  online_sign_ip	varchar(48),
  online_sign_name	varchar(64),

  note_private		text,
  note_public		text,

  model_pdf			varchar(255),
  last_main_doc		varchar(255),				-- relative filepath+filename of last main generated document

  import_key        varchar(14),
  extraparams		varchar(255)				-- for other parameters with json format
)ENGINE=innodb;
