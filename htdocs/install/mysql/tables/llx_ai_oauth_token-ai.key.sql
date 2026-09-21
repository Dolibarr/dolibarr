-- ===================================================================
-- Copyright (C) 2026 Morgan Demoulin <morgan.demoulin@gmail.com>
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

-- The lookup every request makes: type + hash
ALTER TABLE llx_ai_oauth_token ADD UNIQUE INDEX uk_ai_oauth_token_hash (token_type, token_hash);

-- Index for Entity
ALTER TABLE llx_ai_oauth_token ADD INDEX idx_ai_oauth_token_entity (entity);

-- Index for listing or revoking what a user granted
ALTER TABLE llx_ai_oauth_token ADD INDEX idx_ai_oauth_token_fk_user (fk_user);

-- Index for the opportunistic purge of expired rows
ALTER TABLE llx_ai_oauth_token ADD INDEX idx_ai_oauth_token_expires_at (expires_at);
