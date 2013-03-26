-- =============================================================================
-- Copyright (C) 2000-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
-- Copyright (C) 2012	   Juanjo Menent        <jmenent@2byte.es>
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
-- =============================================================================

create table llx_societe_rib
(
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc         integer NOT NULL,
  datec          datetime,
  tms            timestamp,
  label          varchar(30),
  bank           varchar(255),  -- bank name
  code_banque    varchar(7),    -- bank code
  code_guichet   varchar(6),    -- desk code
  number         varchar(255),  -- account number
  cle_rib        varchar(5),    -- key of bank account
  bic            varchar(20),
  iban_prefix    varchar(34),	-- 34 according to ISO 13616
  domiciliation  varchar(255),
  proprio        varchar(60),
  owner_address  varchar(255),
  import_key     varchar(14)    -- import key


)ENGINE=innodb;
