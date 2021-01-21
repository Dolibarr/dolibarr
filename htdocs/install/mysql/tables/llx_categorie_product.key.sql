-- ============================================================================
-- Copyright (C) 2005 Brice Davoleau       <e1davole@iu-vannes.fr>
-- Copyright (C) 2005 Matthieu Valleton    <mv@seeschloss.org>		
-- Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- ============================================================================

ALTER TABLE llx_categorie_product ADD PRIMARY KEY pk_categorie_product (fk_categorie, fk_product);
ALTER TABLE llx_categorie_product ADD INDEX idx_categorie_product_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_product ADD INDEX idx_categorie_product_fk_product (fk_product);

ALTER TABLE llx_categorie_product ADD CONSTRAINT fk_categorie_product_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_product ADD CONSTRAINT fk_categorie_product_product_rowid   FOREIGN KEY (fk_product) REFERENCES llx_product (rowid);
