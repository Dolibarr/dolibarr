-- ============================================================================
-- Copyright (C) 2014 Jean-François Ferry <jfefe@aternatik.fr>
-- 
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
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


ALTER TABLE llx_element_resources ADD UNIQUE INDEX idx_element_resources_idx1 (resource_id, resource_type, element_id, element_type);
ALTER TABLE llx_element_resources ADD INDEX idx_element_element_element_id (element_id);
-- Pas de contraite sur resource_id et element_id car pointe sur differentes tables
	