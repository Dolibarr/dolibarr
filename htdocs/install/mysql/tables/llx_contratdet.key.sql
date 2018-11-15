-- ============================================================================
-- Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2011 Regis Houssin        <regis.houssin@inodbox.com>
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


ALTER TABLE llx_contratdet ADD INDEX idx_contratdet_fk_contrat (fk_contrat);
ALTER TABLE llx_contratdet ADD INDEX idx_contratdet_fk_product (fk_product);
ALTER TABLE llx_contratdet ADD INDEX idx_contratdet_date_ouverture_prevue (date_ouverture_prevue);
ALTER TABLE llx_contratdet ADD INDEX idx_contratdet_date_ouverture (date_ouverture);
ALTER TABLE llx_contratdet ADD INDEX idx_contratdet_date_fin_validite (date_fin_validite);

ALTER TABLE llx_contratdet ADD CONSTRAINT fk_contratdet_fk_contrat FOREIGN KEY (fk_contrat) REFERENCES llx_contrat (rowid);
ALTER TABLE llx_contratdet ADD CONSTRAINT fk_contratdet_fk_product FOREIGN KEY (fk_product) REFERENCES llx_product (rowid);

ALTER TABLE llx_contratdet ADD CONSTRAINT fk_contratdet_fk_unit FOREIGN KEY (fk_unit) REFERENCES llx_c_units (rowid);
