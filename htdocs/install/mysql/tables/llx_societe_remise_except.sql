-- ============================================================================
-- Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- $Id: llx_societe_remise_except.sql,v 1.4 2011/08/03 01:25:40 eldy Exp $
--
-- Remises exceptionnelles
-- ============================================================================

create table llx_societe_remise_except
(
  rowid					integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc				integer NOT NULL, -- client
  datec					datetime,
  amount_ht				double(24,8) NOT NULL,
  amount_tva			double(24,8) DEFAULT 0 NOT NULL,
  amount_ttc			double(24,8) DEFAULT 0 NOT NULL,
  tva_tx				double(6,3)  DEFAULT 0 NOT NULL,
  fk_user				integer NOT NULL,
  fk_facture_line		integer,
  fk_facture			integer,
  fk_facture_source		integer,
  description			varchar(255) NOT NULL
)ENGINE=innodb;
