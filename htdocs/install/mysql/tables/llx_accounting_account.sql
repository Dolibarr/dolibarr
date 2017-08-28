-- ============================================================================
-- Copyright (C) 2004-2006 Laurent Destailleur <eldy@users.sourceforge.net>
-- Copyright (C) 2014	   Juanjo Menent	   <jmenent@2byte.es>
-- Copyright (C) 2016	   Alexandre Spangaro  <aspangaro.dolibarr@gmail.com>
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
-- Table of 'accounts' for accountancy expert module
-- ============================================================================

create table llx_accounting_account
(
  rowid           			integer AUTO_INCREMENT PRIMARY KEY,
  entity          			integer DEFAULT 1 NOT NULL,
  datec           			datetime,
  tms             			timestamp,
  fk_pcg_version  			varchar(32)  NOT NULL,
  pcg_type        			varchar(20)  NOT NULL,
  pcg_subtype     			varchar(20)  NOT NULL,
  account_number  			varchar(32)  NOT NULL,
  account_parent  			varchar(32)  DEFAULT '0',							-- Hierarchic parent TODO Move this as integer, it is a foreign key of llx_accounting_account.rowid
  label           			varchar(255) NOT NULL,
  fk_accounting_category 	integer DEFAULT 0,
  fk_user_author  			integer DEFAULT NULL,
  fk_user_modif   			integer DEFAULT NULL,
  active     	  			tinyint DEFAULT 1  NOT NULL,
  import_key                varchar(14),
  extraparams	            varchar(255)							-- for other parameters with json format
)ENGINE=innodb;
