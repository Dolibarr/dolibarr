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
-- $Source$
--
-- ========================================================================


CREATE TABLE `llx_menu` (
  `rowid` int(11) NOT NULL,
  `mainmenu` varchar(100) NOT NULL,
  `leftmenu` varchar(100) NOT NULL,
  `fk_menu` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `level` tinyint(1) NOT NULL,
  `langs` varchar(100) NOT NULL,
  `right` varchar(255) NOT NULL,
  `target` varchar(100) NOT NULL,
  `user` tinyint(4) NOT NULL default '0',
  `order` tinyint(4) NOT NULL,
  PRIMARY KEY  (`rowid`)
) type=innodb;

