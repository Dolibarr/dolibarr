-- ===================================================================
-- Copyright (C) 2005-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- ===================================================================


-- Supprimme orphelins pour permettre montee de la cle
-- V4 DELETE llx_facture_fourn_det FROM llx_facture_fourn_det LEFT JOIN llx_facture_fourn ON llx_facture_fourn_det.fk_facture_fourn = llx_facture_fourn.rowid WHERE llx_facture_fourn.rowid IS NULL;

ALTER TABLE llx_facture_fourn_det ADD INDEX idx_facture_fourn_det_fk_facture (fk_facture_fourn);
ALTER TABLE llx_facture_fourn_det ADD INDEX idx_facture_fourn_det_fk_product (fk_product);
ALTER TABLE llx_facture_fourn_det ADD INDEX idx_facture_fourn_det_fk_code_ventilation (fk_code_ventilation);
ALTER TABLE llx_facture_fourn_det ADD UNIQUE INDEX uk_fk_remise_except (fk_remise_except, fk_facture_fourn);

ALTER TABLE llx_facture_fourn_det ADD CONSTRAINT fk_facture_fourn_det_fk_facture FOREIGN KEY (fk_facture_fourn) REFERENCES llx_facture_fourn (rowid);
ALTER TABLE llx_facture_fourn_det ADD CONSTRAINT fk_facture_fourn_det_fk_unit FOREIGN KEY (fk_unit) REFERENCES llx_c_units (rowid);
