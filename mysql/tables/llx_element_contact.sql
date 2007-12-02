-- ============================================================================
-- Copyright (C) 2005 patrick Rouillon <patrick@rouillon.net>
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
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- $Id$
-- ============================================================================
-- Association de personnes/societes avec un element de la base (contrat, projet, propal).
-- Permet de definir plusieur type d'intervenant sur un element.
-- i.e. commercial, adresse de facturation, prestataire...
-- ============================================================================

create table llx_element_contact
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,  
  datecreate      datetime NULL, 			-- date de creation de l'enregistrement
  statut          smallint DEFAULT 5, 		-- 5 inactif, 4 actif
  
  element_id		int NOT NULL, 		    -- la reference de l'element.
  fk_c_type_contact	int NOT NULL,	        -- nature du contact.
  fk_socpeople      integer NOT NULL
)type=innodb;

