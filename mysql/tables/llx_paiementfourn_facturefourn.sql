-- ============================================================================
-- Copyright (C) 2003 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
-- Copyright (C) 2005 Marc Barilley / Océbo <marc@ocebo.com>
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
-- ===========================================================================

CREATE TABLE IF NOT EXISTS `llx_paiementfourn_facturefourn` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_paiementfourn` int(11) default NULL,
  `fk_facturefourn` int(11) default NULL,
  `amount` double default '0',
  PRIMARY KEY  (`rowid`),
  KEY `idx_fk_facture` (`fk_facturefourn`),
  KEY `idx_fk_paiement` (`fk_paiementfourn`)
) TYPE=innodb;
