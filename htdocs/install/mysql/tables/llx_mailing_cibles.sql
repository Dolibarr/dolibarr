-- ========================================================================
-- Copyright (C) 2005		Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2009-2016	Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2011-2012	Regis Houssin        <regis.houssin@inodbox.com>
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
-- ========================================================================


create table llx_mailing_cibles
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  fk_mailing		integer NOT NULL,
  fk_contact		integer NOT NULL,
  lastname			varchar(160),
  firstname			varchar(160),
  email				varchar(160) NOT NULL,
  other				varchar(255) NULL,
  tag				varchar(64) NULL,					-- a unique key as a hash of: dolibarr_main_instance_unique_id;email;lastname;mailing_id;MAILING_EMAIL_UNSUBSCRIBE_KEY
  statut			smallint NOT NULL DEFAULT 0,		-- -1 = error, 0 = not sent, ...
  source_url		varchar(255),
  source_id			integer,
  source_type		varchar(16),
  date_envoi		datetime,
  tms				timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  error_text		varchar(255)						-- text with error if statut is -1
)ENGINE=innodb;
