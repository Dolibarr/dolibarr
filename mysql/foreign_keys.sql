-- ============================================================================
-- Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
-- $Id$
-- $Source$
-- ============================================================================

ALTER TABLE llx_facturedet  ADD INDEX (fk_facture);

ALTER TABLE llx_propal      ADD INDEX (fk_soc);

ALTER TABLE llx_fichinter   ADD INDEX (fk_soc);

ALTER TABLE llx_socpeople   ADD INDEX (fk_soc);

ALTER TABLE llx_fichinter        ADD FOREIGN KEY (fk_soc)      REFERENCES llx_societe (idp);

ALTER TABLE llx_propal           ADD FOREIGN KEY (fk_soc)      REFERENCES llx_societe (idp);

ALTER TABLE llx_facture          ADD FOREIGN KEY (fk_soc)      REFERENCES llx_societe (idp);

ALTER TABLE llx_paiement_facture ADD FOREIGN KEY (fk_facture)  REFERENCES llx_facture (rowid);
ALTER TABLE llx_paiement_facture ADD FOREIGN KEY (fk_paiement) REFERENCES llx_paiement (rowid);


ALTER TABLE llx_facturedet       ADD FOREIGN KEY (fk_facture)  REFERENCES llx_facture (rowid);

ALTER TABLE llx_facture_tva_sum  ADD FOREIGN KEY (fk_facture)  REFERENCES llx_facture (rowid);

ALTER TABLE llx_socpeople        ADD FOREIGN KEY (fk_soc)      REFERENCES llx_societe (idp);

ALTER TABLE llx_c_departements   ADD FOREIGN KEY (fk_region)  REFERENCES llx_c_regions (code_region);
ALTER TABLE llx_c_regions        ADD FOREIGN KEY (fk_pays)    REFERENCES llx_c_pays    (rowid);