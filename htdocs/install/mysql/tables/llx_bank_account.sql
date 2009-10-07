-- =============================================================================
-- Copyright (C) 2000-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
-- $Id$
-- =============================================================================

-- courant : type de compte: 0 epargne, 1 courant, 2 caisse
-- clos : le compte est-il clos ou encore ouvert

create table llx_bank_account
(
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  datec          datetime,
  tms            timestamp,
  ref            varchar(12) NOT NULL,
  label          varchar(30) NOT NULL,
  entity         integer DEFAULT 1 NOT NULL,	-- multi company id
  bank           varchar(60),
  code_banque    varchar(7),
  code_guichet   varchar(6),
  number         varchar(255),
  cle_rib        varchar(5),
  bic            varchar(11),
  iban_prefix    varchar(34),                 -- 34 according to ISO 13616
  country_iban   varchar(2),
  cle_iban       varchar(2),
  domiciliation  varchar(255),
  proprio        varchar(60),
  adresse_proprio varchar(255),
  courant        smallint DEFAULT 0 NOT NULL,
  clos           smallint DEFAULT 0 NOT NULL,
  rappro         smallint DEFAULT 1,
  url			       varchar(128),
  account_number varchar(8),
  currency_code  varchar(3) NOT NULL,
  min_allowed    integer DEFAULT 0,
  min_desired    integer DEFAULT 0,
  comment        text
)type=innodb;

-- 
-- List of codes for the field entity
--
-- 1 : first company bank account
-- 2 : second company bank account
-- 3 : etc...
--