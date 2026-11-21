-- ===================================================================
-- Copyright (C) 2026		Jon Bendtsen          		<jon.bendtsen.github@jonb.dk>
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

ALTER TABLE llx_budgetsheet_lines ADD INDEX idx_budgetsheet_lines_fk_budgetsheet (fk_budgetsheet);
ALTER TABLE llx_budgetsheet_lines ADD INDEX idx_budgetsheet_lines_fk_c_type_of_operation (fk_c_type_of_operation);
ALTER TABLE llx_budgetsheet_lines ADD INDEX idx_budgetsheet_lines_date_start (date_start);
ALTER TABLE llx_budgetsheet_lines ADD INDEX idx_budgetsheet_lines_date_end (date_end);
ALTER TABLE llx_budgetsheet_lines ADD INDEX idx_budgetsheet_lines_date_payment_expected (date_payment_expected);
