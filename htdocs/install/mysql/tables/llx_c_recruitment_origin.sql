-- ========================================================================
-- Copyright (C) 2005-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- Defini les types de contact d'un element sert de reference pour
-- la table llx_element_contact
--
-- element est le nom de la table utilisant le type de contact.
-- i.e. contact, facture, projet, societe (sans le llx_ devant).
-- Libelle est un texte decrivant le type de contact.
-- active precise si cette valeur est 'active' ou 'archive'.
--
-- ========================================================================

create table llx_c_recruitment_origin
(
  rowid      	integer AUTO_INCREMENT PRIMARY KEY,
  code          varchar(32) NOT NULL,
  label 	    varchar(64)	NOT NULL,
  active  	    tinyint DEFAULT 1  NOT NULL
)ENGINE=innodb;
