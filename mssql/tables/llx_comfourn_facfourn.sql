-- ===================================================================
-- Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005-2006 Regis Houssin        <regis@dolibarr.fr>
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
-- ===================================================================

create table llx_comfourn_facfourn
(
  rowid       integer IDENTITY PRIMARY KEY,
  fk_commande integer NOT NULL,
  fk_facture  integer NOT NULL,

);

CREATE NONCLUSTERED INDEX llx_comfourn_facfourn ON dbo.llx_comfourn_facfourn
        (
        fk_commande,
        fk_facture
        )
WITH( STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
