-- ============================================================================
-- Copyright (C) 2007 Patrick Raguin       <patrick.raguin@gmail.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
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
-- $Id: llx_categorie_societe.key.sql,v 1.2 2011/08/03 01:25:27 eldy Exp $
-- ============================================================================

ALTER TABLE llx_categorie_societe ADD PRIMARY KEY (fk_categorie, fk_societe);
ALTER TABLE llx_categorie_societe ADD INDEX idx_categorie_societe_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_societe ADD INDEX idx_categorie_societe_fk_societe (fk_societe);

ALTER TABLE llx_categorie_societe ADD CONSTRAINT fk_categorie_societe_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_societe ADD CONSTRAINT fk_categorie_societe_fk_soc   FOREIGN KEY (fk_societe) REFERENCES llx_societe (rowid);
