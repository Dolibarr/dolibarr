-- Generated from dolibarr_mysql2pgsql
-- (c) 2004, PostgreSQL Inc.
-- (c) 2005, Laurent Destailleur.

-- ============================================================================
-- Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- $Id: llx_livraison.key.sql,v 1.1 2006/05/05 15:28:06 hregis Exp $
-- $Source: /cvsroot/dolibarr/dolibarr/mysql/tables/llx_livraison.key.sql,v $
--
-- ============================================================================


-- Supprimme orphelins pour permettre montée de la clé
-- V4 DELETE llx_livraison FROM llx_livraison LEFT JOIN llx_societe ON llx_livraison.fk_soc = llx_societe.idp WHERE llx_societe.idp IS NULL; 

ALTER TABLE llx_livraison ADD INDEX idx_livraison_fk_soc (fk_soc);
ALTER TABLE llx_livraison ADD CONSTRAINT fk_livraison_societe FOREIGN KEY (fk_soc) REFERENCES llx_societe (idp);
