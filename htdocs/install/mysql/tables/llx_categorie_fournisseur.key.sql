-- ============================================================================
-- Copyright (C) 2012 Laurent Destailleur	<eldy@users.sourceforge.net>
-- Copyright (C) 2012 Regis Houssin			<regis.houssin@inodbox.com>
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

ALTER TABLE llx_categorie_fournisseur ADD PRIMARY KEY pk_categorie_fournisseur (fk_categorie, fk_soc);
ALTER TABLE llx_categorie_fournisseur ADD INDEX idx_categorie_fournisseur_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_fournisseur ADD INDEX idx_categorie_fournisseur_fk_societe (fk_soc);

ALTER TABLE llx_categorie_fournisseur ADD CONSTRAINT fk_categorie_fournisseur_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_fournisseur ADD CONSTRAINT fk_categorie_fournisseur_fk_soc   FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
