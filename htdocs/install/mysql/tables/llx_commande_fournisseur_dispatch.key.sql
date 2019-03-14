-- ===================================================================
-- Copyright (C) 2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
-- ===================================================================

ALTER TABLE llx_commande_fournisseur_dispatch ADD INDEX idx_commande_fournisseur_dispatch_fk_commande (fk_commande);
ALTER TABLE llx_commande_fournisseur_dispatch ADD INDEX idx_commande_fournisseur_dispatch_fk_reception (fk_reception);
ALTER TABLE llx_commande_fournisseur_dispatch ADD CONSTRAINT fk_commande_fournisseur_dispatch_fk_reception FOREIGN KEY (fk_reception) REFERENCES llx_reception (rowid);