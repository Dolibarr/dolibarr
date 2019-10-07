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

ALTER TABLE llx_bank ADD INDEX idx_bank_datev(datev);
ALTER TABLE llx_bank ADD INDEX idx_bank_dateo(dateo);
ALTER TABLE llx_bank ADD INDEX idx_bank_fk_account(fk_account);
ALTER TABLE llx_bank ADD INDEX idx_bank_rappro(rappro);
ALTER TABLE llx_bank ADD INDEX idx_bank_num_releve(num_releve);



