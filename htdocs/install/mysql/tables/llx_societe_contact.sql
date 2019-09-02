-- ========================================================================
-- Copyright (C) 2019 Florian HENRY <florian.henry@atm-consulting.fr>
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
-- ========================================================================


create table llx_societe_contact
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,  
  datec      datetime NULL, 			-- date de creation de l'enregistrement
  statut          smallint DEFAULT 5, 		-- 5 inactif, 4 actif
  
  element_id		int NOT NULL, 		    -- la reference de l'element.
  fk_c_type_contact	int NOT NULL,	        -- nature du contact.
  fk_socpeople      integer NOT NULL
)ENGINE=innodb;
