-- ===================================================================
-- Copyright (C) 2010-2011 Regis Houssin  <regis@dolibarr.fr>
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
-- $Id: llx_advanced_extrafields_options.sql,v 1.3 2011/08/08 16:07:33 hregis Exp $
-- ===================================================================

create table llx_advanced_extrafields_options
(
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  
  fk_extrafields 		integer NOT NULL,
  value 				varchar(255) NOT NULL,
  
  pos 					integer
  
)ENGINE=innodb;
