-- ===================================================================
-- Copyright (C) 2022	Laurent Destailleur		<eldy@users.sourceforge.net>
-- Copyright (C) 2024	Maxime Kohlhaas 		<maxime@atm-consulting.fr>
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


ALTER TABLE llx_chargesociales ADD INDEX idx_chargesociales_fk_type (fk_type);
ALTER TABLE llx_chargesociales ADD INDEX idx_chargesociales_fk_account (fk_account);
ALTER TABLE llx_chargesociales ADD INDEX idx_chargesociales_fk_mode_reglement (fk_mode_reglement);
ALTER TABLE llx_chargesociales ADD INDEX idx_chargesociales_fk_user_author (fk_user_author);
ALTER TABLE llx_chargesociales ADD INDEX idx_chargesociales_fk_user_modif (fk_user_modif);
ALTER TABLE llx_chargesociales ADD INDEX idx_chargesociales_fk_user_valid (fk_user_valid);
ALTER TABLE llx_chargesociales ADD INDEX idx_chargesociales_fk_projet (fk_projet);
ALTER TABLE llx_chargesociales ADD INDEX idx_chargesociales_fk_user (fk_user);