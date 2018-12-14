-- ===================================================================
-- Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2006      Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2009      Regis Houssin        <regis.houssin@inodbox.com>
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
--
-- Table with list of document templates for document generation (odt/pdf/...)
-- ===================================================================

create table llx_document_model
(
  	rowid			integer AUTO_INCREMENT PRIMARY KEY,
  	nom				varchar(50),
  	entity			integer DEFAULT 1 NOT NULL,	-- multi company id
  	type			varchar(20) NOT NULL,
  	libelle			varchar(255),
  	description		text
)ENGINE=innodb;

-- 
-- List of codes for the field entity
--
-- 1 : first company document model
-- 2 : second company document model
-- 3 : etc...
--