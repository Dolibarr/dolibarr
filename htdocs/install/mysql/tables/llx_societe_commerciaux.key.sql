-- ===================================================================
-- Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- ===================================================================

ALTER TABLE llx_societe_commerciaux ADD UNIQUE INDEX uk_societe_commerciaux_c_type_contact (fk_soc, fk_user, fk_c_type_contact_code);
ALTER TABLE llx_societe_commerciaux ADD CONSTRAINT fk_societe_commerciaux_fk_c_type_contact_code FOREIGN KEY (fk_c_type_contact_code)  REFERENCES llx_c_type_contact(code);
ALTER TABLE llx_societe_commerciaux ADD CONSTRAINT fk_societe_commerciaux_fk_soc FOREIGN KEY (fk_soc)  REFERENCES llx_societe(rowid);
ALTER TABLE llx_societe_commerciaux ADD CONSTRAINT fk_societe_commerciaux_fk_user FOREIGN KEY (fk_user)  REFERENCES llx_user(rowid);
