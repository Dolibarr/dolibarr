-- Generated from dolibarr_mysql2pgsql
-- (c) 2004, PostgreSQL Inc.
-- (c) 2005, Laurent Destailleur.

-- ===================================================================
-- Copyright (C) 2007      Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2007      Regis Houssin        <regis@dolibarr.fr>
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
-- $Id: llx_export_model.key.sql,v 1.2 2007/09/01 15:03:43 hregis Exp $
-- $Source: /cvsroot/dolibarr/dolibarr/pgsql/tables/llx_export_model.key.sql,v $
--
-- ===================================================================


ALTER TABLE llx_export_model ADD UNIQUE uk_export_model (label);