-- ============================================================================
-- Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
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

ALTER TABLE llx_facture_fourn ADD UNIQUE INDEX uk_facture_fourn_ref (ref, entity);
ALTER TABLE llx_facture_fourn ADD UNIQUE INDEX uk_facture_fourn_ref_supplier (ref_supplier, fk_soc, entity);

ALTER TABLE llx_facture_fourn ADD INDEX idx_facture_fourn_date_lim_reglement (date_lim_reglement);

ALTER TABLE llx_facture_fourn ADD INDEX idx_facture_fourn_fk_soc (fk_soc);
ALTER TABLE llx_facture_fourn ADD INDEX idx_facture_fourn_fk_user_author (fk_user_author);
ALTER TABLE llx_facture_fourn ADD INDEX idx_facture_fourn_fk_user_valid (fk_user_valid);
ALTER TABLE llx_facture_fourn ADD INDEX idx_facture_fourn_fk_projet (fk_projet);

ALTER TABLE llx_facture_fourn ADD CONSTRAINT fk_facture_fourn_fk_soc            FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
ALTER TABLE llx_facture_fourn ADD CONSTRAINT fk_facture_fourn_fk_user_author    FOREIGN KEY (fk_user_author) REFERENCES llx_user (rowid);
ALTER TABLE llx_facture_fourn ADD CONSTRAINT fk_facture_fourn_fk_user_valid     FOREIGN KEY (fk_user_valid) REFERENCES llx_user (rowid);
ALTER TABLE llx_facture_fourn ADD CONSTRAINT fk_facture_fourn_fk_projet         FOREIGN KEY (fk_projet) REFERENCES llx_projet (rowid);
