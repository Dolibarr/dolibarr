-- Copyright (C) 2021 EOXIA <dev@eoxia.com>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.

ALTER TABLE llx_categorie_knowledgemanagement ADD PRIMARY KEY pk_categorie_knowledgemanagement (fk_categorie, fk_knowledgemanagement);
ALTER TABLE llx_categorie_knowledgemanagement ADD INDEX idx_categorie_knowledgemanagement_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_knowledgemanagement ADD INDEX idx_categorie_knowledgemanagement_fk_knowledgemanagement (fk_knowledgemanagement);

ALTER TABLE llx_categorie_knowledgemanagement ADD CONSTRAINT fk_categorie_knowledgemanagement_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_knowledgemanagement ADD CONSTRAINT fk_categorie_knowledgemanagement_knowledgemanagement_rowid   FOREIGN KEY (fk_knowledgemanagement) REFERENCES llx_knowledgemanagement_knowledgerecord (rowid);
