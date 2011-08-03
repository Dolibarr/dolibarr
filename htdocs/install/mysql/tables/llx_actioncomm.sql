-- ========================================================================
-- Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2011      Regis Houssin        <regis@dolibarr.fr>
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
-- $Id: llx_actioncomm.sql,v 1.9 2011/08/03 01:25:33 eldy Exp $
--
-- Actions commerciales
-- ========================================================================

create table llx_actioncomm
(
  id				integer AUTO_INCREMENT PRIMARY KEY,
  entity			integer DEFAULT 1 NOT NULL,	-- multi company id
  datep				datetime,						-- date debut planifiee
  datep2			datetime,						-- deprecated
  datea				datetime,						-- date debut realisation
  datea2			datetime,						-- deprecated

  fk_action			integer,						-- type de l'action
  label				varchar(128) NOT NULL,			-- libelle de l'action

  datec				datetime,						-- date creation
  tms				timestamp,						-- date modif
  fk_user_author	integer,						-- id user qui a cree l'action
  fk_user_mod		integer,						-- id dernier user qui a modifier l'action

  fk_project		integer,
  fk_soc			integer,
  fk_contact		integer,
  fk_parent			integer NOT NULL default 0,

  fk_user_action	integer,						-- id de la personne qui doit effectuer l'action
  fk_user_done		integer,						-- id de la personne qui a effectue l'action
  priority			smallint,
  fulldayevent		smallint NOT NULL default 0,
  punctual			smallint NOT NULL default 1,
  percent			smallint NOT NULL default 0,
  location			varchar(128),
  durationp			real,							-- duree planifiee
  durationa			real,							-- deprecated
  note				text,
  
  fk_element		integer DEFAULT NULL,
  elementtype		varchar(16) DEFAULT NULL

)ENGINE=innodb;




