-- ===================================================================
-- Copyright (C) 2026 Laurent MAGNIN  <laurent.magnin@evarisk.com>
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

ALTER TABLE llx_bank_statement_det ADD INDEX idx_bank_statement_det_fk_bank_statement (fk_bank_statement);
ALTER TABLE llx_bank_statement_det ADD INDEX idx_bank_statement_det_bdate (bdate);
ALTER TABLE llx_bank_statement_det ADD INDEX idx_bank_statement_det_vdate (vdate);
ALTER TABLE llx_bank_statement_det ADD INDEX idx_bank_statement_det_end_to_end_id (end_to_end_id);
ALTER TABLE llx_bank_statement_det ADD INDEX idx_bank_statement_det_fk_bank (fk_bank);
ALTER TABLE llx_bank_statement_det ADD INDEX idx_bank_statement_det_fk_soc (fk_soc);
ALTER TABLE llx_bank_statement_det ADD INDEX idx_bank_statement_det_status (status);
