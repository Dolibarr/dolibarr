-- ============================================================================
-- Copyright (C) 2005 patrick Rouillon <patrick@rouillon.net>
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
-- Associate addresses with elements (contract, project, proposal, ...).
-- ============================================================================

create table llx_element_contact
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  datecreate      datetime NULL, 			-- date of creation of record
  statut          smallint DEFAULT 5, 		-- 5 inactif, 4 actif

  element_id		int NOT NULL, 		    -- ID of element.
  fk_c_type_contact	int NOT NULL,	        -- nature of contact.
  fk_socpeople      integer NOT NULL        -- ID of contact
)ENGINE=innodb;
