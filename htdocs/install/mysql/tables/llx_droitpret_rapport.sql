-- ========================================================================
-- Copyright (C) 2007 Patrick Raguin <patrick.raguin@gmail.com>
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
-- ========================================================================


CREATE TABLE `llx_droitpret_rapport` (
  `rowid` int(11) NOT NULL auto_increment,
  `date_envoie` datetime NOT NULL,
  `format` varchar(10) NOT NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime NOT NULL,
  `fichier` varchar(255) NOT NULL,
  `nbfact` int(11) NOT NULL,
  PRIMARY KEY  (`rowid`)
) type=innodb;
