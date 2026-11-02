-- ===================================================================
-- Copyright (C) 2026       Alexandre Spangaro  <alexandre@inovea-conseil.com>
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

ALTER TABLE llx_payment_various ADD INDEX idx_payment_various_datep (datep);
ALTER TABLE llx_payment_various ADD INDEX idx_payment_various_fk_bank (fk_bank);
ALTER TABLE llx_payment_various ADD INDEX idx_payment_various_fk_projet (fk_projet);
ALTER TABLE llx_payment_various ADD INDEX idx_payment_various_fk_soc (fk_soc);

ALTER TABLE llx_payment_various ADD CONSTRAINT fk_payment_various_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
