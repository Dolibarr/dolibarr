-- ============================================================================
-- Copyright (C) 2013 Florian HENRY <florian.henry@open-concept.pro>
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
-- ============================================================================

CREATE TABLE llx_printer_ipp 
(
 rowid integer AUTO_INCREMENT PRIMARY KEY,
 tms 	timestamp,
 datec 	datetime,
 printer_name text NOT NULL, 
 printer_location text NOT NULL,
 printer_uri varchar(255) NOT NULL,
 copy integer NOT NULL DEFAULT '1',
 module varchar(16) NOT NULL,
 login varchar(32) NOT NULL
)ENGINE=innodb;
