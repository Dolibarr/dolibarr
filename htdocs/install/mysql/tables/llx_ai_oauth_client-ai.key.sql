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

-- A client_id must be unique inside an entity
ALTER TABLE llx_ai_oauth_client ADD UNIQUE INDEX uk_ai_oauth_client_client_id (entity, client_id);

-- Index for Entity
ALTER TABLE llx_ai_oauth_client ADD INDEX idx_ai_oauth_client_entity (entity);

-- Index for the rate limit on self-registration
ALTER TABLE llx_ai_oauth_client ADD INDEX idx_ai_oauth_client_registered_from (registered_from, datec);
