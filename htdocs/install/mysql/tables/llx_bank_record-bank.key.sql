-- ===================================================================
-- Copyright (C) 2025 Florian HENRY  <florian.henry@scopen.fr>
-- Copyright (C) 2025 Laurent MAGNIN  <laurent.magnin@evarisk.com>
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

-- Table to receive manual import of a bank statement
-- Try to match compatibility with external module banking to capitalize on knowledge but removed fields for advanced features.

ALTER TABLE llx_bank_record ADD CONSTRAINT bank_record_fk_bank FOREIGN KEY (fk_bank) REFERENCES llx_bank_account (rowid);
