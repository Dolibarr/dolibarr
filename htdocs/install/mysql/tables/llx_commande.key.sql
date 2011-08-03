-- ============================================================================
-- Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2009 Regis Houssin        <regis@dolibarr.fr>
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
-- $Id: llx_commande.key.sql,v 1.3 2011/08/03 01:25:36 eldy Exp $
-- ============================================================================


-- Supprimme orphelins pour permettre montee de la cle
-- V4 DELETE llx_commande FROM llx_commande LEFT JOIN llx_societe ON llx_commande.fk_soc = llx_societe.rowid WHERE llx_societe.rowid IS NULL; 

ALTER TABLE llx_commande ADD UNIQUE INDEX uk_commande_ref (ref, entity);

ALTER TABLE llx_commande ADD INDEX idx_commande_fk_soc (fk_soc);
ALTER TABLE llx_commande ADD CONSTRAINT fk_commande_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
