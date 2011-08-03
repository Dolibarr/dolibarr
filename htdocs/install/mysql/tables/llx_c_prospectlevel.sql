-- ===================================================================
-- Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- $Id: llx_c_prospectlevel.sql,v 1.4 2011/08/03 01:25:43 eldy Exp $
-- ===================================================================

create table llx_c_prospectlevel
(
  code            varchar(12) PRIMARY KEY,
  label           varchar(30),
  sortorder       smallint,
  active          smallint    DEFAULT 1 NOT NULL,
  module          varchar(32) NULL
) ENGINE=innodb;

