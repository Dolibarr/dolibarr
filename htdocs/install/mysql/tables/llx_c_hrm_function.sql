--
-- Copyright (C) 2013 Jean-François Ferry <jfefe@aternatik.fr>
-- Copyright (C) 2015 Alexandre Spangaro  <aspangaro.dolibarr@gmail.com>
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

create table llx_c_hrm_function
(
  rowid     integer     PRIMARY KEY,
  pos   	tinyint DEFAULT 0 NOT NULL,
  code    	varchar(16) NOT NULL,
  label 	varchar(50),
  c_level   tinyint DEFAULT 0 NOT NULL,
  active  	tinyint DEFAULT 1  NOT NULL
)ENGINE=innodb;

