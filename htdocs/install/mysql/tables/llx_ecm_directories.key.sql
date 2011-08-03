-- ============================================================================
-- Copyright (C) 2010 Laurent Destailleur <eldy@users.sourceforge.net>
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
-- $Id: llx_ecm_directories.key.sql,v 1.2 2011/08/03 01:25:25 eldy Exp $
-- ============================================================================


ALTER TABLE llx_ecm_directories ADD UNIQUE INDEX idx_ecm_directories (label, fk_parent, entity);
	