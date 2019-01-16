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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
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
  entity			integer DEFAULT 1 NOT NULL, -- multi company id
  tms				timestamp,
  datec				datetime,                   -- date de creation 
  date_valid		datetime,                   -- date de validation
  datei				date,						-- date de livraison du bon d'intervention
  fk_user_author	integer,					-- user making creation
  fk_user_modif     integer,                    -- user making last change
  fk_user_valid		integer,                    -- user validating record
  fk_statut			smallint  DEFAULT 0,
  dateo				date,						-- date de début d'intervention
  datee				date,						-- date de fin d'intervention
  datet				date,						-- date de terminaison de l'intervention
  duree				real,                       -- duree totale de l'intervention
  description		text,
  note_private		text,
  note_public		text,
  model_pdf			varchar(255),
  last_main_doc		varchar(255),				-- relative filepath+filename of last main generated document
  import_key        varchar(14),
  extraparams		varchar(255)				-- for other parameters with json format
)ENGINE=innodb;
