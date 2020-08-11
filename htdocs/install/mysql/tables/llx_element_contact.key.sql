-- ============================================================================
-- Copyright (C) 2005 patrick Rouillon    <patrick@rouillon.net>
-- Copyright (C) 2007 Laurent Destailleur <eldy@users.sourceforge.net>
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
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
-- ============================================================================


ALTER TABLE llx_element_contact ADD UNIQUE INDEX idx_element_contact_idx1 (element_id, fk_c_type_contact, fk_socpeople);
	
ALTER TABLE llx_element_contact ADD CONSTRAINT fk_element_contact_fk_c_type_contact FOREIGN KEY (fk_c_type_contact)     REFERENCES llx_c_type_contact(rowid);
	
ALTER TABLE llx_element_contact ADD INDEX idx_element_contact_fk_socpeople (fk_socpeople);
-- Pas de contraite sur fk_socpeople car point sur llx_socpeople mais aussi llx_user
