-- ========================================================================
-- Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- ========================================================================

create table llx_c_forme_juridique
(
  rowid      	integer AUTO_INCREMENT PRIMARY KEY,
  code       	integer NOT NULL,
  fk_pays    	integer NOT NULL,
  libelle    	varchar(255),
  isvatexempted	tinyint DEFAULT 0  NOT NULL,
  active     	tinyint DEFAULT 1  NOT NULL,
  module        varchar(32) NULL,
  position      integer NOT NULL DEFAULT 0
)ENGINE=innodb;

