-- ========================================================================
-- Copyright (C) 2005		Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2009-2012	Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2011-2012	Regis Houssin        <regis@dolibarr.fr>
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
-- ========================================================================


create table llx_mailing_cibles
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  fk_mailing		integer NOT NULL,
  fk_contact		integer NOT NULL,
  nom				varchar(160),
  prenom			varchar(160),
  email				varchar(160) NOT NULL,
  other				varchar(255) NULL,
  tag				varchar(128) NULL,
  statut			smallint NOT NULL DEFAULT 0,
  source_url		varchar(160),
  source_id			integer,
  source_type		varchar(16),
  date_envoi		datetime
  
)ENGINE=innodb;
