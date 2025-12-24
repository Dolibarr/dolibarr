-- ============================================================================
-- Copyright (C) 2016       Charlie Benke       <charlie@patas-monkey.com>
-- Copyright (C) 2016       Frédéric France     <frederic.france@free.fr>
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

ALTER TABLE llx_categorie_account ADD PRIMARY KEY pk_categorie_account (fk_categorie, fk_account);
ALTER TABLE llx_categorie_account ADD INDEX idx_categorie_account_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_account ADD INDEX idx_categorie_account_fk_account (fk_account);

ALTER TABLE llx_categorie_account ADD CONSTRAINT fk_categorie_account_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_account ADD CONSTRAINT fk_categorie_account_fk_account FOREIGN KEY (fk_account) REFERENCES llx_bank_account (rowid);
