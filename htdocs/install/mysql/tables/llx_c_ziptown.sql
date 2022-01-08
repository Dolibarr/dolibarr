-- ========================================================================
-- Copyright (C) 2010 Regis Houssin <regis.houssin@inodbox.com>
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

create table llx_c_ziptown
(
  rowid				integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code				varchar(5) DEFAULT NULL, 		-- ex: code insee pour la France 
  fk_county			integer,	         			-- State id in llx_c_departements
  fk_pays           integer NOT NULL DEFAULT 0,     -- Country id in llx_c_country
  zip	 			varchar(10) NOT NULL,			-- Zip code
  town				varchar(180) NOT NULL,			-- Town name
  active 			tinyint NOT NULL DEFAULT 1
)ENGINE=innodb;
