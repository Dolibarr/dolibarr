-- ===================================================================
-- Copyright (C) 2008-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
-- Copyright (C) 2009-2012	Regis Houssin		<regis@dolibarr.fr>
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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ===================================================================


create table llx_ecm_documents
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  entity			integer DEFAULT 1 NOT NULL,
  ref				varchar(32)  NOT NULL,			-- hash(fullpath + filename + version)
  filename			varchar(255) NOT NULL,
  filesize			integer      NOT NULL,
  filemime			varchar(128) NOT NULL,
  description		text,
  metadata			text,							-- Secure file information (json format / encrypted)
  fullpath    		text,
  fk_directory		integer,
  extraparams		varchar(255),					-- Other parameters (json format)
  fk_create			integer  NOT NULL,
  fk_update			integer,
  date_c			datetime NOT NULL,
  date_u			timestamp,
  fk_status			smallint DEFAULT 0
  
) ENGINE=innodb;
