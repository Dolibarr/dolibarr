-- ========================================================================
-- Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
--
-- $Id$
-- $Source$
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General [public] License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General [public] License for more details.
--
-- You should have received a copy of the GNU General [public] License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- Actions commerciales
--
-- ========================================================================

create table llx_actioncomm
(
  id             int IDENTITY PRIMARY KEY,
  datec          datetime,             -- date creation
  datep          datetime,             -- date debut planifiee
  datep2         datetime,             -- date fin planifiee si action non ponctuelle
  datea          datetime,             -- date debut realisation
  datea2         datetime,             -- date fin realisation si action non ponctuelle
  tms            timestamp,            -- date modif
  fk_action      int,              -- type de l'action
  label          varchar(50) NOT NULL, -- libelle de l'action

  fk_project     int,
  fk_soc         int,
  fk_contact     int,
  fk_parent      int NOT NULL default 0,

  fk_user_action int,              -- id de la personne qui doit effectuer l'action
  fk_user_author int,              -- id de la personne qui a effectuer l'action
  priority       smallint,
  punctual       smallint NOT NULL default 1,
  [percent]        smallint NOT NULL default 0,
  durationp      real,                 -- duree planifiee
  durationa      real,                 -- duree reellement passee
  note           text,

  propalrowid    int,
  fk_commande    int,
  fk_facture     int

);




