-- ===================================================================
-- $Id$
-- $Source$
--
-- $Id$
-- $Source$
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
-- ===================================================================

create table llx_service
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  datec           datetime,
  tms             timestamp,
  ref             varchar(15),
  label           varchar(255),
  description     text,
  price           smallint,
  duration        varchar(32),
  debut_comm      datetime,
  fin_comm        datetime,
  fk_user_auhtor  integer,
  fk_user_modif   integer,

  UNIQUE INDEX(ref)
);

