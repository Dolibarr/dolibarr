-- ============================================================================
-- Copyright (C) 2009 Regis Houssin        <regis.houssin@capnetworks.com>
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
-- ===========================================================================


ALTER TABLE llx_bordereau_chequedet ADD INDEX idx_bordereaudet_fk_bordereau (fk_bordereau);
ALTER TABLE llx_bordereau_chequedet ADD INDEX idx_bordereaudet_fk_bank (fk_bank);
ALTER TABLE llx_bordereau_chequedet ADD INDEX idx_bordereaudet_fk_paiement (fk_paiement);
