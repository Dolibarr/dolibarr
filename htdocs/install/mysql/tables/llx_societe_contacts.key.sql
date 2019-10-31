-- ========================================================================
-- Copyright (C) 2019 Florian HENRY <florian.henry@atm-consulting.fr>
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
-- ========================================================================

ALTER TABLE llx_societe_contacts ADD UNIQUE INDEX idx_societe_contacts_idx1 (entity, fk_soc, fk_c_type_contact, fk_socpeople);
ALTER TABLE llx_societe_contacts ADD CONSTRAINT fk_societe_contacts_fk_c_type_contact FOREIGN KEY (fk_c_type_contact)  REFERENCES llx_c_type_contact(rowid);
ALTER TABLE llx_societe_contacts ADD CONSTRAINT fk_societe_contacts_fk_soc FOREIGN KEY (fk_soc)  REFERENCES llx_societe(rowid);
ALTER TABLE llx_societe_contacts ADD CONSTRAINT fk_societe_contacts_fk_socpeople FOREIGN KEY (fk_socpeople)  REFERENCES llx_socpeople(rowid);
