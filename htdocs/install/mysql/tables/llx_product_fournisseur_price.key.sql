-- ============================================================================
-- Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
-- Copyright (C) 2005-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
-- Copyright (C) 2009-2012	Regis Houssin			<regis@dolibarr.fr>
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


ALTER TABLE llx_product_fournisseur_price ADD INDEX idx_product_fournisseur_price_fk_user (fk_user);

ALTER TABLE llx_product_fournisseur_price ADD CONSTRAINT fk_product_fournisseur_price_fk_user    FOREIGN KEY (fk_user)    REFERENCES llx_user (rowid);

-- Added to remove table llx_product_fournisseur

ALTER TABLE llx_product_fournisseur_price ADD UNIQUE INDEX uk_product_fournisseur_price_ref (ref_fourn, fk_soc, quantity, entity);

ALTER TABLE llx_product_fournisseur_price ADD INDEX idx_product_fourn_price_fk_product (fk_product, entity);
ALTER TABLE llx_product_fournisseur_price ADD INDEX idx_product_fourn_price_fk_soc (fk_soc, entity);

ALTER TABLE llx_product_fournisseur_price ADD CONSTRAINT fk_product_fournisseur_price_fk_product FOREIGN KEY (fk_product) REFERENCES llx_product (rowid);
