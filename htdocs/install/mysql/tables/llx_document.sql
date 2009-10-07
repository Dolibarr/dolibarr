-- ===================================================================
-- Copyright (C) 2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

create table llx_document
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  name            varchar(255) NOT NULL,
  file_name       varchar(255) NOT NULL,
  file_extension  varchar(5)   NOT NULL,
  date_generation datetime     NULL,
  fk_owner        integer      NULL,
  fk_group        integer      NULL,
  permissions     char(9)      DEFAULT 'rw-rw-rw'

)type=innodb;
