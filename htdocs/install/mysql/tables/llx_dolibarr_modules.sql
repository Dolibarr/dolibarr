-- ========================================================================
-- Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
-- $Id: llx_dolibarr_modules.sql,v 1.3 2011/08/03 01:25:24 eldy Exp $
-- ========================================================================

create table llx_dolibarr_modules
(
  numero         integer,
  entity         integer     DEFAULT 1 NOT NULL,	-- multi company id
  active         tinyint     DEFAULT 0 NOT NULL,
  active_date    datetime    NOT NULL,
  active_version varchar(25) NOT NULL

)ENGINE=innodb;


