-- ============================================================================
-- Copyright (C) 2005 Brice Davoleau <e1davole@iu-vannes.fr>
-- Copyright (C) 2005 Matthieu Valleton <mv@seeschloss.org>		
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
-- $Id: llx_categorie_lead.sql,v 1.0 2011/04/04 18:18:06 eldy Exp $
-- ============================================================================

create table llx_categorie_contact
(
  fk_categorie  integer NOT NULL,
  fk_contact    integer NOT NULL
)type=innodb;
