-- Module to manage resources into Dolibarr ERP/CRM
-- Copyright (C) 2013	Jean-François Ferry	<jfefe@aternatik.fr>
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
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.


ALTER TABLE llx_resource ADD UNIQUE INDEX uk_resource_ref (ref, entity);

ALTER TABLE llx_resource ADD INDEX fk_code_type_resource_idx (fk_code_type_resource);

ALTER TABLE llx_resource ADD INDEX idx_resource_fk_country (fk_country);
ALTER TABLE llx_resource ADD CONSTRAINT fk_resource_fk_country FOREIGN KEY (fk_country) REFERENCES llx_c_country (rowid);
