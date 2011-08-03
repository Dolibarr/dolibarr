-- ============================================================================
-- Copyright (C) 2005 Laurent Destailleur <eldy@users.sourceforge.net>
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
-- $Id: llx_c_departements.key.sql,v 1.2 2011/08/03 01:25:31 eldy Exp $
-- ============================================================================


ALTER TABLE llx_c_departements ADD UNIQUE uk_departements (code_departement,fk_region);


ALTER TABLE llx_c_departements ADD INDEX idx_departements_fk_region (fk_region);
