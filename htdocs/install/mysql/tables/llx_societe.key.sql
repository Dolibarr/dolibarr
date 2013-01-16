-- ===================================================================
-- Copyright (C) 2005 Laurent Destailleur <eldy@users.sourceforge.net>
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
-- ===================================================================


ALTER TABLE llx_societe ADD UNIQUE uk_societe_prefix_comm(prefix_comm, entity);
ALTER TABLE llx_societe ADD UNIQUE uk_societe_code_client(code_client, entity);

ALTER TABLE llx_societe ADD INDEX idx_societe_user_creat(fk_user_creat);
ALTER TABLE llx_societe ADD INDEX idx_societe_user_modif(fk_user_modif);

-- ALTER TABLE llx_societe ADD FOREIGN KEY fk_prospectlevel llx_c_prospectlevel(code);
