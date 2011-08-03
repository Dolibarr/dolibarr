-- ============================================================================
-- Copyright (C) 2004-2006 Laurent Destailleur <eldy@users.sourceforge.net>
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
-- $Id: llx_accountingsystem.sql,v 1.3 2011/08/03 01:25:32 eldy Exp $
-- Table of "Plan de comptes" for accountancy expert module
-- ============================================================================

create table llx_accountingsystem
(
  pcg_version       varchar(12)     PRIMARY KEY,
  fk_pays           integer         NOT NULL,
  label             varchar(128)    NOT NULL,
  datec             varchar(12)     NOT NULL,
  fk_author         varchar(20),
  tms               timestamp,
  active            smallint        DEFAULT 0
)ENGINE=innodb;
