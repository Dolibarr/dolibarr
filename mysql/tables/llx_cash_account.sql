-- =============================================================================
-- Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- $Id$
-- $Source$
--
--
-- courant : indique si c'est un compte courant
-- clos : le compte est-il clos ou encore ouvert
--
-- =============================================================================

create table llx_cash_account
(
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  datec          datetime,
  tms            timestamp,
  label          varchar(30),
  courant        smallint DEFAULT 0 NOT NULL,
  clos           smallint DEFAULT 0 NOT NULL,
  account_number varchar(8)
)type=innodb;
