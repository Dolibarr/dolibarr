-- ============================================================================
-- Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
-- ===========================================================================


-- Delete orphans
-- V4 DELETE llx_paiement_facture FROM llx_paiement_facture LEFT JOIN llx_facture ON llx_paiement_facture.fk_facture = llx_facture.rowid WHERE llx_facture.rowid IS NULL;
-- V4 DELETE llx_paiement_facture FROM llx_paiement_facture LEFT JOIN llx_paiement ON llx_paiement_facture.fk_facture = llx_paiement.rowid WHERE llx_paiement.rowid IS NULL;

ALTER TABLE llx_paiement_facture ADD INDEX idx_paiement_facture_fk_facture (fk_facture);
ALTER TABLE llx_paiement_facture ADD CONSTRAINT fk_paiement_facture_fk_facture FOREIGN KEY (fk_facture) REFERENCES llx_facture (rowid);

ALTER TABLE llx_paiement_facture ADD INDEX idx_paiement_facture_fk_paiement (fk_paiement);
ALTER TABLE llx_paiement_facture ADD CONSTRAINT fk_paiement_facture_fk_paiement FOREIGN KEY (fk_paiement) REFERENCES llx_paiement (rowid);


ALTER TABLE llx_paiement_facture ADD UNIQUE INDEX uk_paiement_facture(fk_paiement, fk_facture);

