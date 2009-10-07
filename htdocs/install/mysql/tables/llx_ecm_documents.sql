-- ===================================================================
-- Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- ===================================================================

-- drop table llx_ecm_document;

create table llx_ecm_document
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  ref             varchar(16)  NOT NULL,
  filename        varchar(255) NOT NULL,
  filesize        integer      NOT NULL,
  filemime        varchar(32)  NOT NULL,
  fullpath_dol    varchar(255) NOT NULL,
  fullpath_orig   varchar(255) NOT NULL,
  description     text,
  manualkeyword   text,
  fk_create       integer  NOT NULL,
  fk_update       integer,
  date_c	      datetime NOT NULL,
  date_u		  timestamp,
  fk_directory    integer,
  fk_status		  smallint DEFAULT 0,
  private         smallint DEFAULT 0
) type=innodb;
