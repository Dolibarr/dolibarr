-- ========================================================================
-- Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- $Id: llx_c_currencies.sql,v 1.4 2011/08/03 01:25:39 eldy Exp $
-- ========================================================================

create table llx_c_currencies
(
  code        varchar(2)   PRIMARY KEY,
  code_iso    varchar(3)   NOT NULL,
  label       varchar(64),
  labelsing   varchar(64),
  active      tinyint DEFAULT 1  NOT NULL
)ENGINE=innodb;

