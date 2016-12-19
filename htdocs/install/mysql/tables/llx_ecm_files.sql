-- ===================================================================
-- Copyright (C) 2015	Laurent Destailleur	<eldy@users.sourceforge.net>
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
-- ===================================================================

CREATE TABLE llx_ecm_files
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  label				varchar(64) NOT NULL,
  entity			integer DEFAULT 1 NOT NULL,		-- multi company id
  filename          varchar(255) NOT NULL,			-- file name only without any directory
  fullpath    		varchar(750) NOT NULL,   	    -- relative to dolibarr document dir. example abc/def/myfile
  fullpath_orig		varchar(750),	                -- full path of original filename, when file is uploaded from a local computer
  description		text,
  keywords          text,                           -- list of keywords, separated with comma
  cover             text,                           -- is this file a file to use for a cover
  gen_or_uploaded   varchar(12),                    -- 'generated' or 'uploaded' 
  extraparams		varchar(255),					-- for stocking other parameters with json format
  date_c			datetime,
  date_m			timestamp,
  fk_user_c			integer,
  fk_user_m			integer,
  acl				text							-- for future permission 'per file'
) ENGINE=innodb;
