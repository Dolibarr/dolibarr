-- ========================================================================
-- Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2011      Regis Houssin        <regis.houssin@capnetworks.com>
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
--
-- Actions commerciales
-- ========================================================================

create table llx_actioncomm
(
  id				integer AUTO_INCREMENT PRIMARY KEY,
  ref_ext			varchar(128),
  entity			integer DEFAULT 1 NOT NULL,		-- multi company id
  datep				datetime,						-- date start
  datep2			datetime,						-- date end
  datea				datetime,						-- deprecated
  datea2			datetime,						-- deprecated

  fk_action			integer,						-- type of action (optionnal link with llx_c_actioncomm or null)
  code				varchar(32) NULL,				-- code of action for automatic action
  label				varchar(128) NOT NULL,			-- label/title of event
  
  datec				datetime,						-- date creation
  tms				timestamp,						-- date modification
  fk_user_author	integer,						-- user id of user that has created record
  fk_user_mod		integer,						-- user id of user that has modified record

  fk_project		integer,
  fk_soc			integer,
  fk_contact		integer,
  fk_parent			integer NOT NULL default 0,

  fk_user_action	integer,						-- user id of owner of action (currently also user id of actor that must do action. In future, actors assigned to action will be an array into table llx_actioncomm_actors)

  transparency      integer,						-- transparency (ical standard). used to say if people assigned to event are busy or not by event (in future version, this field is deprecated and will be stored into table llx_actioncomm_actors)
  fk_user_done		integer,						-- user id of people that has made action (deprecated)
  
  priority			smallint,
  fulldayevent		smallint NOT NULL default 0,
  punctual			smallint NOT NULL default 1,
  percent			smallint NOT NULL default 0,
  location			varchar(128),
  durationp			real,							-- duree planifiee
  durationa			real,							-- deprecated
  note				text,
  
  fk_element		integer DEFAULT NULL,
  elementtype		varchar(32) DEFAULT NULL

)ENGINE=innodb;




