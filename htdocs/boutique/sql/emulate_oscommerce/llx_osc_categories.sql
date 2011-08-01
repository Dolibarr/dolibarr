-- ===================================================================
-- Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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

CREATE TABLE llx_osc_categories (
  rowid int(11) unsigned NOT NULL auto_increment,
  dolicatid int(11) NOT NULL default '0',
  osccatid int(11) NOT NULL default '0',
  PRIMARY KEY  (rowid),
  UNIQUE KEY dolicatid (dolicatid),
  UNIQUE KEY osccatid (osccatid)
) TYPE=InnoDB COMMENT='Correspondance categorie Dolibarr categorie OSC';
