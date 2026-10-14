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

ALTER TABLE llx_bank_record_link ADD CONSTRAINT fk_bank_record_bank_record FOREIGN KEY (fk_bank_record) REFERENCES llx_bank_record (rowid);
ALTER TABLE llx_bank_record_link ADD CONSTRAINT fk_bank_import_bank_import FOREIGN KEY (fk_bank_import) REFERENCES llx_bank_import (rowid);
