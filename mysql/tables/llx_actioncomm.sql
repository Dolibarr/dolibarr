-- ========================================================================
-- Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
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
--
-- Actions commerciales
-- ========================================================================

create table llx_actioncomm
(
  id             integer AUTO_INCREMENT PRIMARY KEY,
  datep          datetime,             -- date debut planifiee
  datep2         datetime,             -- date fin planifiee si action non ponctuelle
  datea          datetime,             -- date debut realisation
  datea2         datetime,             -- date fin realisation si action non ponctuelle

  fk_action      integer,              -- type de l'action
  label          varchar(50) NOT NULL, -- libelle de l'action

  datec          datetime,             -- date creation
  tms            timestamp,            -- date modif
  fk_user_create integer,              -- id user qui a cree l'action
  fk_user_mod    integer,              -- id dernier user qui a modifier l'action

  fk_project     integer,
  fk_soc         integer,
  fk_contact     integer,
  fk_parent      integer NOT NULL default 0,

  fk_user_action integer,              -- id de la personne qui doit effectuer l'action
  fk_user_author integer,              -- id de la personne qui a effectue l'action
  priority       smallint,
  punctual       smallint NOT NULL default 1,
  percent        smallint NOT NULL default 0,
  durationp      real,                 -- duree planifiee
  durationa      real,                 -- duree reellement passee
  note           text,

  propalrowid    integer,
  fk_commande    integer,
  fk_facture     integer

)type=innodb;




