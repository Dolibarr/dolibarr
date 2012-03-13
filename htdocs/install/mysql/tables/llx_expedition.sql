-- ===================================================================
-- Copyright (C) 2003-2010 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2008-2010 Regis Houssin        <regis@dolibarr.fr>
-- Copyright (C) 2011      Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2012      Juanjo Menent		<jmenent@2byte.es>
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
-- ===================================================================

create table llx_expedition
(
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  tms                   timestamp,
  ref                   varchar(30)        NOT NULL,
  entity                integer  DEFAULT 1 NOT NULL,	-- multi company id
  fk_soc                integer            NOT NULL,
  
  ref_ext               varchar(30),					-- reference into an external system (not used by dolibarr)
  ref_int				varchar(30),					-- reference into an internal system (used by dolibarr)
  ref_customer          varchar(30),					-- customer number
  
  date_creation         datetime,						-- date de creation
  fk_user_author        integer,						-- createur
  date_valid            datetime,						-- date de validation
  fk_user_valid         integer,						-- valideur
  date_expedition       datetime,						-- shipping date
  date_delivery			datetime	DEFAULT NULL,		-- delivery date
  fk_address  			integer		DEFAULT NULL, 		-- delivery address (deprecated)
  fk_expedition_methode integer,
  tracking_number       varchar(50),
  fk_statut             smallint	DEFAULT 0,
  
  height                integer,
  width                 integer,
  size_units            integer,
  size                  integer,
  weight_units          integer,
  weight                integer,
  note                  text,
  model_pdf             varchar(255)
  
)ENGINE=innodb;
