-- ============================================================================
-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2008      Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
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
-- ============================================================================

create table llx_socpeople
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  datec				datetime,
  tms				timestamp,
  fk_soc			integer,									-- lien vers la societe
  entity			integer DEFAULT 1 NOT NULL,					-- multi company id
  ref_ext           varchar(255),                               -- reference into an external system (not used by dolibarr)
  civility			varchar(6),
  lastname			varchar(50),
  firstname			varchar(50),
  address			varchar(255),
  zip				varchar(25),
  town				varchar(255),
  fk_departement	integer,
  fk_pays			integer        DEFAULT 0,
  birthday			date,
  poste				varchar(80),
  phone				varchar(30),
  phone_perso		varchar(30),
  phone_mobile		varchar(30),
  fax				varchar(30),
  email				varchar(255),
  
  jabberid			varchar(255),
  skype				varchar(255),
  twitter			varchar(255),                        		--
  facebook			varchar(255),                        		--
  linkedin            			varchar(255),                       		--
  instagram                varchar(255),                        		--
  snapchat                 varchar(255),                        		--
  googleplus               varchar(255),                        		--
  youtube                  varchar(255),                        		--
  whatsapp                 varchar(255),                        		--
  
  photo				varchar(255),
  no_email			smallint NOT NULL DEFAULT 0,				-- deprecated. Use table llx_mailing_unsubscribe instead
  priv				smallint NOT NULL DEFAULT 0,
  fk_user_creat		integer DEFAULT 0,							-- user qui a creel'enregistrement
  fk_user_modif		integer,
  note_private		text,
  note_public		text,
  default_lang		varchar(6),
  canvas			varchar(32),			-- type of canvas if used (null by default)
  import_key		varchar(14),
  statut			tinyint DEFAULT 1 NOT NULL
)ENGINE=innodb;
