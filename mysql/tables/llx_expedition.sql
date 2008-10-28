-- ===================================================================
-- Copyright (C) 2003-2008 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2008      Regis Houssin        <regis@dolibarr.fr>
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
  fk_soc                integer     NOT NULL,
  date_creation         datetime,                -- date de creation
  fk_user_author        integer,                 -- createur
  date_valid            datetime,                -- date de validation
  fk_user_valid         integer,                 -- valideur
  date_expedition       date,                    -- date de l'expedition
  fk_adresse_livraison  integer   DEFAULT NULL,  -- adresse de livraison
  fk_expedition_methode integer,
  tracking_number       varchar(50),
  fk_statut             smallint  DEFAULT 0,
  height 				integer,
  width 				integer,
  size_units 			integer,
  size 					integer,
  weight_units 			integer,
  weight 				integer,
  note                  text,
  model_pdf             varchar(50)
)type=innodb;
