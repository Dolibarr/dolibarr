-- ============================================================================
-- Copyright (C) 2003-2006 Rodolphe Quiedeville	<rodolphe@quiedeville.org>
-- Copyright (C) 2004-2017 Laurent Destailleur	<eldy@users.sourceforge.net>
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
-- ============================================================================

create table llx_entrepot
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  ref             varchar(255) NOT NULL,
  datec           datetime,
  tms             timestamp,
  entity          integer DEFAULT 1 NOT NULL,	-- multi company id
  description     text,
  lieu            varchar(64),					-- resume lieu situation
  address         varchar(255),
  zip             varchar(10),
  town            varchar(50),
  fk_departement  integer,
  fk_pays         integer DEFAULT 0,
  statut          tinyint DEFAULT 1,			-- 1 open, 0 close
  fk_user_author  integer,
  model_pdf       varchar(255),
  import_key	    varchar(14),
  fk_parent       integer DEFAULT 0
)ENGINE=innodb;
