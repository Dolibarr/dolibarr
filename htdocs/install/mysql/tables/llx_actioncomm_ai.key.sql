-- ========================================================================
-- Copyright (C) 2026 Braito <braito4@hotmail.com>
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
-- ========================================================================

ALTER TABLE llx_actioncomm_ai ADD INDEX idx_actioncomm_ai_fk_actioncomm (fk_actioncomm);
ALTER TABLE llx_actioncomm_ai ADD INDEX idx_actioncomm_ai_entity_operation (entity, operation_code, status);
ALTER TABLE llx_actioncomm_ai ADD INDEX idx_actioncomm_ai_input_hash (entity, input_hash);
ALTER TABLE llx_actioncomm_ai ADD INDEX idx_actioncomm_ai_security_hash (entity, security_hash);

ALTER TABLE llx_actioncomm_ai ADD CONSTRAINT fk_actioncomm_ai_fk_actioncomm FOREIGN KEY (fk_actioncomm) REFERENCES llx_actioncomm (id);
