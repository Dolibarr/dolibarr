-- ============================================================================
-- Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2009      Regis Houssin        <regis.houssin@inodbox.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
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
-- ============================================================================


-- Delete orphans
-- V4 DELETE llx_commande_fournisseur FROM llx_commande_fournisseur LEFT JOIN llx_societe ON llx_commande_fournisseur.fk_soc = llx_societe.rowid WHERE llx_societe.rowid IS NULL; 

ALTER TABLE llx_commande_fournisseur ADD UNIQUE INDEX uk_commande_fournisseur_ref (ref, fk_soc, entity);

ALTER TABLE llx_commande_fournisseur ADD INDEX idx_commande_fournisseur_fk_soc (fk_soc);
ALTER TABLE llx_commande_fournisseur ADD CONSTRAINT fk_commande_fournisseur_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
ALTER TABLE llx_commande_fournisseur ADD INDEX billed (billed);
