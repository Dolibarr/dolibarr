-- ============================================================================
-- Copyright (C) 2004-2006  Laurent Destailleur <eldy@users.sourceforge.net>
-- Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
-- Copyright (C) 2016-2020  Alexandre Spangaro  <aspangaro@open-dsi.fr>
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
-- Table of 'accounts' for accountancy expert module
-- ============================================================================

create table llx_accounting_account
(
  rowid                     bigint AUTO_INCREMENT PRIMARY KEY,
  entity                    integer DEFAULT 1 NOT NULL,
  datec                     datetime,
  tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_pcg_version            varchar(32)  NOT NULL,			  -- Chart system
  pcg_type                  varchar(60)  NOT NULL,			  -- First part of Key for predefined groups
  account_number            varchar(32)  NOT NULL,
  account_parent            integer DEFAULT 0,                -- Hierarchic parent.
  label                     varchar(255) NOT NULL,
  labelshort				varchar(255) DEFAULT NULL,
  fk_accounting_category    integer      DEFAULT 0,			  -- ID of personalized group for report
  fk_user_author            integer      DEFAULT NULL,
  fk_user_modif             integer      DEFAULT NULL,
  active                    tinyint      DEFAULT 1  NOT NULL,
  reconcilable				tinyint      DEFAULT 0  NOT NULL,
  import_key                varchar(14),
  extraparams               varchar(255)                      -- for other parameters with json format
)ENGINE=innodb DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
