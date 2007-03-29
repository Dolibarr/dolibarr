-- ========================================================================
-- Copyright (C) 2004-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
--
--

CREATE TABLE llx_telephonie_tarif_montant (
  rowid          int(11)       NOT NULL auto_increment,
  fk_tarif_desc  int(11)       NOT NULL default '0',
  fk_tarif       int(11)       NOT NULL default '0',
  temporel       double                 default '0',
  fixe           double                 default '0',
  fk_user        int(11)       NOT NULL default '0',
  tms            timestamp(14) NOT NULL,
  tmpid          int(11)                default NULL,
  PRIMARY KEY  (rowid),
  UNIQUE KEY fk_tarif_desc (fk_tarif_desc,fk_tarif)

) TYPE=InnoDB;




