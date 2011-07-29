-- ===================================================================
-- Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
-- $Id: llx_fichinter.sql,v 1.5 2011/08/03 01:25:35 eldy Exp $
-- ===================================================================

create table llx_fichinter
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc          integer NOT NULL,
  fk_projet       integer DEFAULT 0,          -- projet auquel est rattache la fiche
  fk_contrat      integer DEFAULT 0,          -- contrat auquel est rattache la fiche
  ref             varchar(30) NOT NULL,       -- number
  entity          integer DEFAULT 1 NOT NULL, -- multi company id
  tms             timestamp,
  datec           datetime,                   -- date de creation 
  date_valid      datetime,                   -- date de validation
  datei           date,                       -- date de livraison du bon d'intervention
  fk_user_author  integer,                    -- createur de la fiche
  fk_user_valid   integer,                    -- valideur de la fiche
  fk_statut       smallint  DEFAULT 0,
  duree           real,                       -- duree totale de l'intervention
  description     text,
  note_private    text,
  note_public     text,
  model_pdf       varchar(255)
  
)ENGINE=innodb;
