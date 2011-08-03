-- ============================================================================
-- Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
-- $Id: llx_product_fournisseur.key.sql,v 1.4 2011/08/03 01:25:40 eldy Exp $
-- ============================================================================


ALTER TABLE llx_product_fournisseur ADD UNIQUE INDEX uk_product_fournisseur_ref (ref_fourn, fk_soc, entity);

ALTER TABLE llx_product_fournisseur ADD INDEX idx_product_fourn_fk_product (fk_product, entity);
ALTER TABLE llx_product_fournisseur ADD INDEX idx_product_fourn_fk_soc (fk_soc, entity);

ALTER TABLE llx_product_fournisseur ADD CONSTRAINT fk_product_fournisseur_fk_product FOREIGN KEY (fk_product) REFERENCES llx_product (rowid);
