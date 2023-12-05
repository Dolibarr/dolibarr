--
-- Copyright (C) 2013 Jean-François Ferry <jfefe@aternatik.fr>
-- Copyright (C) 2015 Alexandre Spangaro  <aspangaro@open-dsi.fr>
-- Copyright (C) 2022 Juanjo Menent       <jmenent@2byte.es>
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
-- along with this program.  If not, see <https://www.gnu.org/licenses/>.

create table llx_c_hrm_department
(
  rowid      	integer     PRIMARY KEY,
  pos   		tinyint DEFAULT 0 NOT NULL,
  code    		varchar(16) NOT NULL,
  label 		varchar(128),
  active  		tinyint DEFAULT 1  NOT NULL
)ENGINE=innodb;

