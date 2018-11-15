-- ============================================================================
-- Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2012      Cédric Salvador      <csalvador@gpcsolutions.fr>
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
ALTER TABLE llx_product ADD INDEX idx_product_fk_country (fk_country);
ALTER TABLE llx_product ADD INDEX idx_product_fk_user_author (fk_user_author);
ALTER TABLE llx_product ADD INDEX idx_product_fk_barcode_type (fk_barcode_type);
ALTER TABLE llx_product ADD UNIQUE INDEX uk_product_barcode (barcode, fk_barcode_type, entity);
ALTER TABLE llx_product ADD CONSTRAINT fk_product_fk_unit FOREIGN KEY (fk_unit) REFERENCES llx_c_units (rowid);

ALTER TABLE  llx_product ADD CONSTRAINT fk_product_fk_country FOREIGN KEY (fk_country) REFERENCES  llx_c_country (rowid);
ALTER TABLE  llx_product ADD CONSTRAINT fk_product_barcode_type FOREIGN KEY (fk_barcode_type) REFERENCES  llx_c_barcode_type (rowid);
ALTER TABLE  llx_product ADD CONSTRAINT fk_product_default_warehouse FOREIGN KEY (fk_default_warehouse) REFERENCES llx_entrepot (rowid);
