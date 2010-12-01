-- ===========================================================================
-- Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- ===========================================================================

CREATE TABLE llx_pos_tmp (
  id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
  fk_article integer NOT NULL,
  qte real NOT NULL,
  fk_tva integer NOT NULL,
  remise_percent real NOT NULL,
  remise real NOT NULL,
  total_ht double(24,8) NOT NULL,
  total_tva double(24,8) NOT NULL,
  total_ttc double(24,8) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=innodb;
