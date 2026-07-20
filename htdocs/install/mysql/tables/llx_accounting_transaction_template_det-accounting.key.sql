-- ============================================================================
-- Copyright (C) 2024		AWeerWolf
-- Copyright (C) 2026		Alexandre Spangaro	 <alexandre@inovea-conseil.com>
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
-- Table of line of transaction template for accounting
-- ============================================================================

ALTER TABLE llx_accounting_transaction_template_det ADD INDEX idx_accounting_transaction_template_det_rowid (rowid);
ALTER TABLE llx_accounting_transaction_template_det ADD CONSTRAINT llx_accounting_transaction_template_det_fk_transaction_template FOREIGN KEY (fk_transaction_template) REFERENCES llx_accounting_transaction_template(rowid);
