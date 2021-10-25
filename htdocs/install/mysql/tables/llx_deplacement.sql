-- ============================================================================
-- Copyright (C) 2003		Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2009-2012	Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2010		Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
-- ============================================================================

create table llx_deplacement
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  ref				varchar(30) DEFAULT NULL,		-- Ref donation (TODO change to NOT NULL)
  entity			integer DEFAULT 1 NOT NULL,		-- multi company id
  datec				datetime NOT NULL,
  tms				timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  dated				datetime,
  fk_user			integer NOT NULL,
  fk_user_author	integer,
  fk_user_modif		integer,						-- utilisateur qui a modifie l'info
  type				varchar(12) NOT NULL,
  fk_statut         integer DEFAULT 1 NOT NULL,
  km				real,
  fk_soc			integer,
  fk_projet         integer DEFAULT 0,
  note_private	    text,
  note_public       text,
  extraparams		varchar(255)					-- for stock other parameters with json format
  
)ENGINE=innodb;
