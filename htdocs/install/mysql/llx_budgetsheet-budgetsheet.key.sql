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

ALTER TABLE llx_budgetsheet ADD UNIQUE INDEX uk_budgetsheet_ref (ref, entity);
ALTER TABLE llx_budgetsheet ADD INDEX idx_budgetsheet_fk_project (fk_project);
ALTER TABLE llx_budgetsheet ADD INDEX idx_budgetsheet_fk_task (fk_task);
ALTER TABLE llx_budgetsheet ADD INDEX idx_budgetsheet_fk_status (fk_status);
ALTER TABLE llx_budgetsheet ADD INDEX idx_budgetsheet_date_create (date_create);
ALTER TABLE llx_budgetsheet ADD INDEX idx_budgetsheet_date_valid (date_valid);
ALTER TABLE llx_budgetsheet ADD INDEX idx_budgetsheet_date_start (date_start);
ALTER TABLE llx_budgetsheet ADD INDEX idx_budgetsheet_date_end (date_end);
ALTER TABLE llx_budgetsheet ADD INDEX idx_budgetsheet_fk_user_author (fk_user_author);
ALTER TABLE llx_budgetsheet ADD INDEX idx_budgetsheet_fk_user_valid (fk_user_valid);
ALTER TABLE llx_budgetsheet ADD INDEX idx_budgetsheet_fk_user_approve (fk_user_approve);
ALTER TABLE llx_budgetsheet ADD INDEX idx_budgetsheet_fk_user_cancel (fk_user_cancel);
