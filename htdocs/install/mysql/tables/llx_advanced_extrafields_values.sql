-- ===================================================================
-- Copyright (C) 2010 Regis Houssin  <regis@dolibarr.fr>
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
-- $Id: llx_advanced_extrafields_values.sql,v 1.2 2011/08/03 01:25:39 eldy Exp $
-- ===================================================================

create table llx_advanced_extrafields_values
(
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  tms                   timestamp,
  entity                integer  DEFAULT 1 NOT NULL,	 -- multi company id
  
  datec					datetime,
  datem					datetime,
  fk_element 			integer NOT NULL,                -- id of object (rowid of proposal, order, invoice...)
  fk_extrafields		integer NOT NULL,                -- key to attribute definition
  value					varchar(255),                    -- value of attribute

  fk_user_create 		integer,
  fk_user_modif 		integer
  
)ENGINE=innodb;
