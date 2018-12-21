-- ============================================================================
-- Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
-- Copyright (C) 2006-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
-- Copyright (C) 2006-2012	Regis Houssin			<regis.houssin@inodbox.com>
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
-- ===========================================================================

--
-- position  : 0=Home page index.php
-- box_order : Box sort order
--

create table llx_boxes
(
  rowid			integer AUTO_INCREMENT PRIMARY KEY,
  entity		integer NOT NULL DEFAULT 1,
  box_id		integer NOT NULL,
  position		smallint NOT NULL,
  box_order		varchar(3) NOT NULL,
  fk_user		integer default 0 NOT NULL,
  maxline		integer NULL,
  params		varchar(255)
)ENGINE=innodb;
