-- ============================================================================
-- Copyright (C) 2012 Laurent Destailleur	<eldy@users.sourceforge.net>
-- Copyright (C) 2012 Regis Houssin			<regis.houssin@inodbox.com>
-- Copyright (C) 2023 Charlene Benke		<charlene@patas-monkey.com>
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

ALTER TABLE llx_categorie_fichinter ADD PRIMARY KEY pk_categorie_fichinter (fk_categorie, fk_fichinter);
ALTER TABLE llx_categorie_fichinter ADD INDEX idx_categorie_fichinter_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_fichinter ADD INDEX idx_categorie_fichinter_fk_fichinter (fk_fichinter);

ALTER TABLE llx_categorie_fichinter ADD CONSTRAINT fk_categorie_fichinter_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_fichinter ADD CONSTRAINT fk_categorie_fichinter_fk_fichinter    FOREIGN KEY (fk_fichinter) REFERENCES llx_fichinter (rowid);
