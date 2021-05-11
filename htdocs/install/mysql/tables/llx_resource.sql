-- Module to manage resources into Dolibarr ERP/CRM
-- Copyright (C) 2013       Jean-François Ferry   <jfefe@aternatik.fr>
-- Copyright (C) 2016		    Gilles Poirier		    <glgpoirier@gmail.com>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.

CREATE TABLE llx_resource
(
  rowid           		  integer AUTO_INCREMENT PRIMARY KEY,
  entity          		  integer DEFAULT 1 NOT NULL,
  ref             		  varchar(255),
  asset_number          varchar(255),
  description     		  text,
  fk_code_type_resource varchar(32),
  datec                 datetime DEFAULT NULL,
  date_valid            datetime DEFAULT NULL,
  fk_user_author        integer DEFAULT NULL,
  fk_user_modif         integer DEFAULT NULL,
  fk_user_valid         integer DEFAULT NULL,
  fk_statut             smallint NOT NULL DEFAULT '0',
  note_public     		  text,
  note_private    		  text,
  import_key			varchar(14),
  extraparams			varchar(255),					-- for stock other parameters with json format
  fk_country          integer DEFAULT NULL,          -- Optional id of original country
  tms         			  timestamp
)ENGINE=innodb;
