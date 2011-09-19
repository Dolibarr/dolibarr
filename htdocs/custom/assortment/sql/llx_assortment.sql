-- ========================================================================
-- Copyright (C) 2011 Florian HENRY <florian.henry.mail@gmail.com>
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
-- $Id: llx_assortment.sql,v 3.0 2011/01/01
--
-- Assortment
-- ========================================================================

create table llx_assortment
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  label          varchar(128) NOT NULL,  -- libelle de l assortiment

  datec          datetime,             -- date creation
  tms            timestamp,            -- date modif
  fk_user_author integer,              -- id user qui a cree l'action
  fk_user_mod    integer,              -- id dernier user qui a modifier l'action

  fk_soc         integer NOT NULL,
  fk_prod        integer NOT NULL
  
)type=innodb;




