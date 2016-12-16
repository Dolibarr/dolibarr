-- ============================================================================
-- Copyright (C) 2013	Laurent Destailleur	<eldy@users.sourceforge.net>
-- Copyright (C) 2013	Florian Henry		<florian.henry@open-concept.pro>
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


ALTER TABLE llx_actioncomm_resources ADD UNIQUE INDEX uk_actioncomm_resources(fk_actioncomm, element_type, fk_element);
ALTER TABLE llx_actioncomm_resources ADD INDEX idx_actioncomm_resources_fk_element (fk_element);

-- Pas de contrainte sur fk_source et fk_target car pointe sur differentes tables
	
