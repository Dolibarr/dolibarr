-- Generated from dolibarr_mysql2pgsql
-- (c) 2004, PostgreSQL Inc.
-- (c) 2005, Laurent Destailleur.

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
-- $Id: llx_osc_customer.sql,v 1.2 2007/06/11 22:52:15 hregis Exp $
-- $Source: /cvsroot/dolibarr/dolibarr/mysql/tables/llx_osc_customer.sql,v $
--
-- ===================================================================

CREATE TABLE IF NOT EXISTS `llx_osc_customer` (
  "`rowid`" int4 NOT NULL default '0',
  "`datem`" timestamp default NULL,
  "`fk_soc`" int4 NOT NULL default '0',
  PRIMARY KEY  (`rowid`),
  "UNIQUE"   UNIQUE  (`fk_soc`)
) TYPE=InnoDB COMMENT='Table transition client OSC - societe Dolibarr';
