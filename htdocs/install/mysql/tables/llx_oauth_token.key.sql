-- ============================================================================
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
-- ============================================================================

-- Lookup of a hashed credential. Rows that do not use token_hash leave it NULL,
-- and NULLs never collide in a unique index, so other services are unaffected.
ALTER TABLE llx_oauth_token ADD UNIQUE INDEX uk_oauth_token_service_hash (service, token_hash);
ALTER TABLE llx_oauth_token ADD INDEX idx_oauth_token_fk_oauth_client (fk_oauth_client);
