-- ===================================================================
-- Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

create table llx_expedition
(
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  tms                   timestamp,
  ref                   varchar(30) NOT NULL,
  fk_commande           integer,
  date_creation         datetime,              -- date de creation 
  date_valid            datetime,              -- date de validation
  date_expedition       date,                  -- date de l'expedition
  fk_user_author        integer,               -- createur
  fk_user_valid         integer,               -- valideur
  fk_entrepot           integer,
  fk_expedition_methode integer,
  fk_statut             smallint  DEFAULT 0,
  note                  text,
  model_pdf             varchar(50),

  UNIQUE INDEX (ref),
  key(fk_expedition_methode),
  key(fk_commande)
)type=innodb;
