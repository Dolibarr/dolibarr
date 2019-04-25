-- ===================================================================
-- Copyright (C) 2011-2014	Juanjo Menent	<jmenent@2byte.es>
-- Copyright (C) 2011		Regis Houssin	<regis.houssin@inodbox.com>
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
-- ===================================================================

create table llx_localtax
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  entity          integer DEFAULT 1 NOT NULL,
  localtaxtype    tinyint,
  tms             timestamp,
  datep           date,								-- date of payment
  datev           date,								-- date of value
  amount          double,
  label           varchar(255),	
  note            text,
  fk_bank         integer,  
  fk_user_creat   integer,                 
  fk_user_modif   integer 
)ENGINE=innodb;
