-- <Product - Quote - PDF>
-- Copyright (C) 2013	Florian HENRY <florian.henry@open-concept.pro>
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

CREATE TABLE llx_propal_merge_pdf_product (
  rowid integer NOT NULL auto_increment PRIMARY KEY,
  fk_product integer NOT NULL,
  file_name varchar(200) NOT NULL,
  lang 	varchar(5) DEFAULT NULL,
  fk_user_author integer DEFAULT NULL,
  fk_user_mod integer NOT NULL,
  datec datetime NOT NULL,
  tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  import_key varchar(14) DEFAULT NULL
) ENGINE=innodb;

