-- ============================================================================
-- Copyright (C) 2007-2009 Laurent Destailleur <eldy@users.sourceforge.net>
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

create table llx_texts
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,

  module          varchar(32),                  -- Nom du module en rapport avec le modele
  typemodele      varchar(32),                  -- Type du modele
  sortorder       smallint,						-- Ordre affichage
  
  private         smallint DEFAULT 0 NOT NULL,  -- Modele publique ou prive
  fk_user         integer,                      -- Id utilisateur si modele prive, sinon null
  title           varchar(128),                 -- Titre du modele
  filename		  varchar(128),					-- Nom fichier si modele fichier
  content         text,                         -- Texte si modele texte

  tms             timestamp
)ENGINE=innodb;
