-- ============================================================================
-- Copyright (C) 2003 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
-- Copyright (C) 2005 Marc Barilley / Océbo <marc@ocebo.com>
-- Copyright (C) 2005 Laurent Destailleur   <eldy@users.sourceforge.net>
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
-- ===========================================================================

create table llx_paiementfourn_facturefourn
(
  rowid integer IDENTITY PRIMARY KEY,
  fk_paiementfourn integer DEFAULT NULL,
  fk_facturefourn  integer DEFAULT NULL,
  amount FLOAT(25) DEFAULT '0'
);