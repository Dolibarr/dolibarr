-- ========================================================================
-- Copyright (C) 2011 Philippe GRAND      <philippe.grand@atoo-net.com>
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
-- $Id: llx_c_delivery.sql,v 1.2 2011/02/24 09:57:04 hregis Exp $
-- ========================================================================

create table llx_c_delivery
(
  `rowid` int(11) NOT NULL auto_increment,
  `code` varchar(30) default NULL,
  `libelle` varchar(60) default NULL,
  `active` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`rowid`)
)ENGINE=innodb;

--
-- Contenu de la table `llx_c_delivery`
--

INSERT INTO `llx_c_delivery` (`rowid`, `code`, `libelle`, `active`) VALUES
(1, 'DSP', 'Disponible', 1),
(2, 'USM', 'Une semaine', 1),
(3, 'DSM', 'Deux semaines', 1),
(4, 'TSM', 'Trois semaines', 1);

