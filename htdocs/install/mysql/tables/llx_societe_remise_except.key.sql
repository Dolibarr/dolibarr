-- ============================================================================
-- Copyright (C) 2004		Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005-2007	Laurent Destailleur  <eldy@users.sourceforge.net>
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
--
-- Remises exceptionnelles
--
-- ============================================================================


ALTER TABLE llx_societe_remise_except ADD INDEX idx_societe_remise_except_fk_user (fk_user);
ALTER TABLE llx_societe_remise_except ADD INDEX idx_societe_remise_except_fk_soc (fk_soc);
ALTER TABLE llx_societe_remise_except ADD INDEX idx_societe_remise_except_fk_facture_line (fk_facture_line);
ALTER TABLE llx_societe_remise_except ADD INDEX idx_societe_remise_except_fk_facture (fk_facture);
ALTER TABLE llx_societe_remise_except ADD INDEX idx_societe_remise_except_fk_facture_source (fk_facture_source);
ALTER TABLE llx_societe_remise_except ADD INDEX idx_societe_remise_except_discount_type (discount_type);


ALTER TABLE llx_societe_remise_except ADD CONSTRAINT fk_societe_remise_fk_user    FOREIGN KEY (fk_user)    REFERENCES llx_user (rowid);
ALTER TABLE llx_societe_remise_except ADD CONSTRAINT fk_soc_remise_fk_soc     FOREIGN KEY (fk_soc)     REFERENCES llx_societe (rowid);

ALTER TABLE llx_societe_remise_except ADD CONSTRAINT fk_soc_remise_fk_facture_line       FOREIGN KEY (fk_facture_line) REFERENCES llx_facturedet (rowid);
ALTER TABLE llx_societe_remise_except ADD CONSTRAINT fk_societe_remise_fk_facture        FOREIGN KEY (fk_facture)        REFERENCES llx_facture (rowid);
ALTER TABLE llx_societe_remise_except ADD CONSTRAINT fk_societe_remise_fk_facture_source FOREIGN KEY (fk_facture_source) REFERENCES llx_facture (rowid);

ALTER TABLE llx_societe_remise_except ADD CONSTRAINT fk_soc_remise_fk_invoice_supplier_line       FOREIGN KEY (fk_invoice_supplier_line) REFERENCES llx_facture_fourn_det (rowid);
ALTER TABLE llx_societe_remise_except ADD CONSTRAINT fk_societe_remise_fk_invoice_supplier        FOREIGN KEY (fk_invoice_supplier)      REFERENCES llx_facture_fourn (rowid);
ALTER TABLE llx_societe_remise_except ADD CONSTRAINT fk_societe_remise_fk_invoice_supplier_source FOREIGN KEY (fk_invoice_supplier)      REFERENCES llx_facture_fourn (rowid);


