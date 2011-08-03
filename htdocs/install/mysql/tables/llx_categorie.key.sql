-- ============================================================================
-- Copyright (C) 2007-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2009      Regis Houssin        <regis@dolibarr.fr>
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
-- $Id: llx_categorie.key.sql,v 1.2 2011/08/03 01:25:27 eldy Exp $
-- ============================================================================

ALTER TABLE llx_categorie ADD UNIQUE INDEX uk_categorie_ref (label,type,entity);

ALTER TABLE llx_categorie ADD INDEX idx_categorie_type (type);
