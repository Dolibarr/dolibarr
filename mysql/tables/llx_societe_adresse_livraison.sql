-- ========================================================================
-- Copyright (C) 2000-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005-2006 Houssin Régis        <regis@dolibarr.fr>
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
-- ========================================================================

create table llx_societe_adresse_livraison
(
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  datec	             datetime,                            -- creation date
  tms                timestamp,                           -- modification date
  label              varchar(30),                         --
  fk_societe         integer        DEFAULT 0,            --
  nom                varchar(60),                         -- company name
  address            varchar(255),                        -- company adresse
  cp                 varchar(10),                         -- zipcode
  ville              varchar(50),                         -- town
  fk_pays            integer        DEFAULT 0,            --
  note               text,                                --
  fk_user_creat      integer,                             -- utilisateur qui a créé l'info
  fk_user_modif      integer                              -- utilisateur qui a modifié l'info
)type=innodb;