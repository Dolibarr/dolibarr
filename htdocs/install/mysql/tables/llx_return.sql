-- ===================================================================
-- Copyright (C) 2003-2010 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2008-2010 Regis Houssin        <regis.houssin@capnetworks.com>
-- Copyright (C) 2011-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2012      Juanjo Menent		<jmenent@2byte.es>
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
-- ===================================================================

create table llx_return
(
  rowid					integer AUTO_INCREMENT PRIMARY KEY,
  tms					timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  ref					varchar(30)        NOT NULL,
  label					varchar(255),
  entity				integer  DEFAULT 1 NOT NULL,	-- multi company id
  fk_soc				integer            NOT NULL,
  fk_projet				integer DEFAULT NULL,

  ref_ext				varchar(30),					-- reference into an external system (not used by dolibarr)
  ref_int				varchar(30),					-- reference into an internal system (used by dolibarr)
  ref_customer			varchar(30),					-- customer number

  date_creation			datetime,						-- date de creation
  fk_user_author		integer,						-- createur
  date_valid			datetime,						-- date de validation
  fk_user_valid			integer,						-- valideur
  date_return			datetime	DEFAULT NULL,		-- return date
  fk_user_modif         integer		DEFAULT NULL,
  fk_statut				smallint	DEFAULT 0,

  note_private			text,
  note_public			text,
  model_pdf				varchar(255),
  extraparams			varchar(255)					-- for other parameters with json format

)ENGINE=innodb;
