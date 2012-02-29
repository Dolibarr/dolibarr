-- ============================================================================
-- Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
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
-- ============================================================================


ALTER TABLE llx_facture ADD UNIQUE INDEX idx_facture_uk_facnumber (facnumber, entity);

ALTER TABLE llx_facture ADD INDEX idx_facture_fk_soc (fk_soc);
ALTER TABLE llx_facture ADD INDEX idx_facture_fk_user_author (fk_user_author);
ALTER TABLE llx_facture ADD INDEX idx_facture_fk_user_valid (fk_user_valid);
ALTER TABLE llx_facture ADD INDEX idx_facture_fk_facture_source (fk_facture_source);
ALTER TABLE llx_facture ADD INDEX idx_facture_fk_projet (fk_projet);
ALTER TABLE llx_facture ADD INDEX idx_facture_fk_account (fk_account);
ALTER TABLE llx_facture ADD INDEX idx_facture_fk_currency (fk_currency);

ALTER TABLE llx_facture ADD CONSTRAINT fk_facture_fk_soc            FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
ALTER TABLE llx_facture ADD CONSTRAINT fk_facture_fk_user_author    FOREIGN KEY (fk_user_author) REFERENCES llx_user (rowid);
ALTER TABLE llx_facture ADD CONSTRAINT fk_facture_fk_user_valid     FOREIGN KEY (fk_user_valid)  REFERENCES llx_user (rowid);
ALTER TABLE llx_facture ADD CONSTRAINT fk_facture_fk_facture_source FOREIGN KEY (fk_facture_source) REFERENCES llx_facture (rowid);
ALTER TABLE llx_facture ADD CONSTRAINT fk_facture_fk_projet         FOREIGN KEY (fk_projet) REFERENCES llx_projet (rowid);
ALTER TABLE llx_facture ADD CONSTRAINT fk_facture_fk_currency       FOREIGN KEY (fk_currency) REFERENCES llx_c_currencies (code);