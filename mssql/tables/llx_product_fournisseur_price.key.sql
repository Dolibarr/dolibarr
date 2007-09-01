-- ============================================================================
-- Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General [public] License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General [public] License for more details.
--
-- You should have received a copy of the GNU General [public] License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- $Id$
-- $Source$
--
-- ============================================================================


CREATE INDEX idx_product_fournisseur_price_fk_user ON llx_product_fournisseur_price(fk_user);
CREATE INDEX idx_product_fournisseur_price_fk_soc ON llx_product_fournisseur_price(fk_soc);
CREATE INDEX idx_product_fournisseur_price_fk_product ON llx_product_fournisseur_price(fk_product);

ALTER TABLE llx_product_fournisseur_price ADD CONSTRAINT fk_product_fournisseur_price_fk_user    FOREIGN KEY (fk_user)    REFERENCES llx_user (rowid);
ALTER TABLE llx_product_fournisseur_price ADD CONSTRAINT fk_product_fournisseur_price_fk_soc     FOREIGN KEY (fk_soc)     REFERENCES llx_societe (rowid);
ALTER TABLE llx_product_fournisseur_price ADD CONSTRAINT fk_product_fournisseur_price_fk_product FOREIGN KEY (fk_product) REFERENCES llx_product (rowid);


