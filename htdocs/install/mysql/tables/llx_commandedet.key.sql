-- ===================================================================
-- Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2011 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2012      Cédric Salvador      <csalvador@gpcsolutions.fr>
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
-- ===================================================================



ALTER TABLE llx_commandedet ADD INDEX idx_commandedet_fk_commande (fk_commande);
ALTER TABLE llx_commandedet ADD INDEX idx_commandedet_fk_product (fk_product);

ALTER TABLE llx_commandedet ADD CONSTRAINT fk_commandedet_fk_unit FOREIGN KEY (fk_unit) REFERENCES llx_c_units (rowid);
ALTER TABLE llx_commandedet ADD CONSTRAINT fk_commandedet_fk_commande FOREIGN KEY (fk_commande) REFERENCES llx_commande (rowid);
ALTER TABLE llx_commandedet ADD CONSTRAINT fk_commandedet_fk_commandefourndet FOREIGN KEY (fk_commandefourndet) REFERENCES llx_commande_fournisseurdet (rowid);
--ALTER TABLE llx_commandedet ADD CONSTRAINT fk_commandedet_fk_multicurrency FOREIGN KEY (fk_multicurrency) REFERENCES llx_multicurrency (rowid);
--ALTER TABLE llx_commandedet ADD CONSTRAINT fk_commandedet_fk_product_fournisseur_price FOREIGN KEY (fk_product_fournisseur_price) REFERENCES llx_product_fournisseur_price (rowid);
--ALTER TABLE llx_commandedet ADD CONSTRAINT fk_commandedet_fk_remise_except FOREIGN KEY (fk_remise_except) REFERENCES llx_societe_remise_except (rowid);

