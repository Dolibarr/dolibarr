-- ===================================================================
-- Copyright (C) 2026 Nick Fragoulis
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
-- ===================================================================

-- A state is looked up by its hash on every confirmation, and must be unique
ALTER TABLE llx_ai_write_confirmation ADD UNIQUE INDEX uk_ai_write_confirmation_state (state_hash, entity);

-- Purge of expired rows, and listing what a user proposed
ALTER TABLE llx_ai_write_confirmation ADD INDEX idx_ai_write_confirmation_expiration (date_expiration);
ALTER TABLE llx_ai_write_confirmation ADD INDEX idx_ai_write_confirmation_fk_user (fk_user);
