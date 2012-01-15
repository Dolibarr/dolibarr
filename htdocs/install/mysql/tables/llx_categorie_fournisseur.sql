-- ============================================================================
-- Copyright (C) 2006 Rodolphe Quiedeville	<rodolphe@quiedeville.org>
-- Copyright (C) 2012 Laurent Destailleur	<eldy@users.sourceforge.net>
-- Copyright (C) 2012 Regis Houssin			<regis@dolibarr.fr>
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
-- ============================================================================

create table llx_categorie_fournisseur
(
  fk_categorie  integer NOT NULL,
  fk_societe    integer NOT NULL
)ENGINE=innodb;
