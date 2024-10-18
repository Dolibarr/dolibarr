-- ============================================================================
-- Copyright (C) 2024      Support              <support@easya.solutions>
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


-- Delete orphans
-- V4 DELETE llx_supplier_contractdet FROM llx_supplier_contractdet, llx_supplier_contract LEFT JOIN llx_societe ON llx_supplier_contract.fk_soc = llx_societe.rowid WHERE llx_supplier_contract.fk_supplier_contract = llx_supplier_contract.rowid AND llx_societe.rowid IS NULL;
-- V4 DELETE llx_supplier_contract FROM llx_supplier_contract LEFT JOIN llx_societe ON llx_supplier_contract.fk_soc = llx_societe.rowid WHERE llx_societe.rowid IS NULL;
-- V4 DELETE llx_supplier_contract FROM llx_supplier_contract LEFT JOIN llx_user ON llx_supplier_contract.fk_user_creat = llx_user.rowid WHERE llx_user.rowid IS NULL;

ALTER TABLE llx_supplier_contract ADD UNIQUE INDEX uk_supplier_contract_ref (ref, entity);

ALTER TABLE llx_supplier_contract ADD INDEX idx_supplier_contract_fk_soc (fk_soc);
ALTER TABLE llx_supplier_contract ADD INDEX idx_supplier_contract_fk_user_creat (fk_user_creat);

ALTER TABLE llx_supplier_contract ADD CONSTRAINT fk_supplier_contract_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);
ALTER TABLE llx_supplier_contract ADD CONSTRAINT fk_supplier_contract_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user (rowid);
