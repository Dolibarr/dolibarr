-- ===================================================================
-- Copyright (C) 2017   Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2017	ATM Consulting		 <support@atm-consulting.fr>
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
-- ===================================================================

CREATE TABLE llx_inventory 
( 
  rowid integer NOT NULL AUTO_INCREMENT PRIMARY KEY, 
  entity integer DEFAULT 0, 
  ref varchar(48),
  date_creation datetime DEFAULT NULL,
  tms timestamp, 
  fk_user_creat	integer,					-- user making creation
  fk_user_modif integer,                    -- user making last change
  fk_user_valid integer,                    -- valideur de la fiche
  fk_warehouse integer DEFAULT NULL, 
  fk_product integer DEFAULT NULL, 
  status integer DEFAULT 0, 
  title varchar(255) NOT NULL, 
  date_inventory datetime DEFAULT NULL,
  date_validation datetime DEFAULT NULL,
  import_key               varchar(14)       	-- import key
) 
ENGINE=innodb;
