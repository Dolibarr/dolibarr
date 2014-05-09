-- ============================================================================
-- Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
-- Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
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


ALTER TABLE llx_product ADD UNIQUE INDEX uk_product_ref (ref, entity);

ALTER TABLE llx_product ADD INDEX idx_product_label (label);
ALTER TABLE llx_product ADD INDEX idx_product_barcode (barcode);
ALTER TABLE llx_product ADD INDEX idx_product_import_key (import_key);
ALTER TABLE llx_product ADD INDEX idx_product_seuil_stock_alerte (seuil_stock_alerte);
ALTER TABLE llx_product ADD INDEX (  fk_country );
ALTER TABLE llx_product ADD INDEX (  fk_user_author );
ALTER TABLE llx_product ADD INDEX (  fk_barcode_type );
ALTER TABLE llx_product ADD INDEX (  fk_parent );
ALTER TABLE llx_product ADD UNIQUE INDEX uk_product_barcode (barcode, fk_barcode_type, entity);


ALTER TABLE  llx_product ADD FOREIGN KEY (  fk_country ) REFERENCES  llx_c_pays (
rowid
) ON DELETE RESTRICT ON UPDATE RESTRICT ;

ALTER TABLE  llx_product ADD FOREIGN KEY (  fk_user_author ) REFERENCES  llx_user (
rowid
) ON DELETE RESTRICT ON UPDATE RESTRICT ;

ALTER TABLE  llx_product ADD FOREIGN KEY (  fk_barcode_type ) REFERENCES  llx_c_barcode_type (
rowid
) ON DELETE RESTRICT ON UPDATE RESTRICT ;

ALTER TABLE  llx_product ADD FOREIGN KEY (  fk_parent ) REFERENCES  llx_product (
rowid
) ON DELETE RESTRICT ON UPDATE RESTRICT ;