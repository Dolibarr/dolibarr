-- ============================================================================
-- Copyright (C) 2020 Laurent Destailleur <eldy@users.sourceforge.net>
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

ALTER TABLE llx_categorie_website_page ADD PRIMARY KEY pk_categorie_website_page (fk_categorie, fk_website_page);
ALTER TABLE llx_categorie_website_page ADD INDEX idx_categorie_website_page_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_website_page ADD INDEX idx_categorie_website_page_fk_website_page (fk_website_page);

ALTER TABLE llx_categorie_website_page ADD CONSTRAINT fk_categorie_websitepage_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_website_page ADD CONSTRAINT fk_categorie_websitepage_website_page_rowid FOREIGN KEY (fk_website_page) REFERENCES llx_website_page (rowid);

