-- ============================================================================
-- Copyright (C) 2026		Alexandre Spangaro   	<alexandre@inovea-conseil.com>
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
-- ============================================================================

-- Enforces that each account (or sub-account) can only appear once per fiscal year and entity.
ALTER TABLE llx_accounting_balance_snapshot ADD UNIQUE INDEX uk_accounting_balance_snapshot(entity, fk_fiscalyear, account_number, subledger_account);

ALTER TABLE llx_accounting_balance_snapshot ADD INDEX idx_accounting_balance_snapshot_account (entity, fk_fiscalyear, account_number, debit, credit);
ALTER TABLE llx_accounting_balance_snapshot ADD INDEX idx_accounting_balance_snapshot_subaccount (entity, fk_fiscalyear, subledger_account, debit, credit);
