-- ===================================================================
-- Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2009      Regis Houssin        <regis@dolibarr.fr>
-- Copyright (C) 2011      Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- $Id: llx_extrafields.sql,v 1.3 2011/08/08 23:24:30 eldy Exp $
-- ===================================================================

create table llx_extrafields
(
	rowid           integer AUTO_INCREMENT PRIMARY KEY,
	name            varchar(64) NOT NULL,       -- nom de l'attribut
	entity          integer DEFAULT 1 NOT NULL,	-- multi company id
    elementtype     varchar(64) NOT NULL DEFAULT 'member',
	tms             timestamp,
	label           varchar(255) NOT NULL,      -- label correspondant a l'attribut
	type            varchar(8),
	size            integer DEFAULT 0,
	pos             integer DEFAULT 0
)ENGINE=innodb;
