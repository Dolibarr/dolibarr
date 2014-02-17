-- ============================================================================
-- Copyright (C) 2011-2014 Alexandre Spangaro   <alexandre.spangaro@gmail.com>
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

ALTER TABLE llx_categorie_employee ADD PRIMARY KEY pk_categorie_employee (fk_categorie, fk_employee);
ALTER TABLE llx_categorie_employee ADD INDEX idx_categorie_employee_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_employee ADD INDEX idx_categorie_employee_fk_member (fk_employee);

ALTER TABLE llx_categorie_employee ADD CONSTRAINT fk_categorie_employee_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_employee ADD CONSTRAINT fk_categorie_employee_employee_rowid FOREIGN KEY (fk_employee) REFERENCES llx_employee (rowid);
