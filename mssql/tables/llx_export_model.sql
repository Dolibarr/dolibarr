-- ===================================================================
-- Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2007      Regis Houssin        <regis@dolibarr.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General [public] License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General [public] License for more details.
--
-- You should have received a copy of the GNU General [public] License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- $Id$
-- $Source$
--
-- Liste des modeles de document disponibles
--
-- ===================================================================

create table llx_export_model
(
  	rowid         integer IDENTITY PRIMARY KEY,
	fk_user		  integer DEFAULT 0 NOT NULL,
  	label         varchar(50) NOT NULL,
  	type		  varchar(20) NOT NULL,
  	field         text NOT NULL
);