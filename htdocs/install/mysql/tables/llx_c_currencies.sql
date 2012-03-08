-- ========================================================================
-- Copyright (C) 2005	Laurent Destailleur	<eldy@users.sourceforge.net>
-- Copyright (C) 2012	Regis Houssin		<regis@dolibarr.fr>
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
-- ========================================================================

create table llx_c_currencies
(
  code_iso		varchar(3)  PRIMARY KEY,
  label			varchar(64) NOT NULL,
  unicode		varchar(32) DEFAULT NULL,
  active		tinyint		DEFAULT 1  NOT NULL
  
)ENGINE=innodb;

