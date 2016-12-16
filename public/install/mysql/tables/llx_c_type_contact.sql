-- ========================================================================
-- Copyright (C) 2005     Patrick Rouillon     <patrick.rouillon.net>
-- Copyright (C) 2005     Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- Defini les types de contact d'un element sert de reference pour
-- la table llx_element_contact
--
-- element est le nom de la table utilisant le type de contact.
-- i.e. contact, facture, projet, societe (sans le llx_ devant).
-- Libelle est un texte decrivant le type de contact.
-- active precise si cette valeur est 'active' ou 'archive'.
--
-- ========================================================================


create table llx_c_type_contact
(
  rowid      	integer     PRIMARY KEY,
  element       varchar(30) NOT NULL,
  source        varchar(8)  DEFAULT 'external' NOT NULL,
  code          varchar(32) NOT NULL,
  libelle 	    varchar(64)	NOT NULL,
  active  	    tinyint DEFAULT 1  NOT NULL,
  module        varchar(32) NULL,
  position      integer NOT NULL DEFAULT 0
)ENGINE=innodb;
