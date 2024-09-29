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
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
-- ===================================================================

CREATE TABLE llx_ecm_files
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  ref				varchar(128),					-- contains hash from filename+filepath
  label				varchar(128) NOT NULL,			-- contains hash of file content
  share				varchar(128) NULL,				-- contains hash for file sharing
  share_pass		varchar(32) NULL,				-- password to access the file (encoded with dolEncrypt)
  entity			integer DEFAULT 1 NOT NULL,		-- multi company id
  filepath    		varchar(255) NOT NULL,   	    -- relative to dolibarr document dir. Example module/def
  filename          varchar(255) NOT NULL,			-- file name only without any directory
  src_object_type   varchar(64),	         		-- Source object type ('proposal', 'invoice', ...) - object->table_element
  src_object_id     integer,		             	-- Source object id
  fullpath_orig		varchar(750),	                -- full path of original filename, when file is uploaded from a local computer
  description		text,
  keywords          varchar(750),                   -- list of keywords, separated with comma. Must be limited to most important keywords.
  cover             text,                           -- is this file a file to use for a cover
  position          integer,                        -- position of file among others
  gen_or_uploaded   varchar(12),                    -- 'generated' or 'uploaded' 
  extraparams		varchar(255),					-- for stocking other parameters with json format
  date_c			datetime,
  tms				timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_c			integer,
  fk_user_m			integer,
  note_private		text,
  note_public		text,
  acl				text							-- for future permission 'per file'
) ENGINE=innodb;
