-- ============================================================================
-- Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
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
-- ============================================================================


ALTER TABLE llx_bank_account ADD UNIQUE uk_bank_account_label (label,entity);
ALTER TABLE llx_bank_account ADD INDEX idx_fk_accountancy_journal (fk_accountancy_journal);

ALTER TABLE llx_bank_account ADD CONSTRAINT fk_bank_account_accountancy_journal FOREIGN KEY (fk_accountancy_journal) REFERENCES llx_accounting_journal (rowid);
