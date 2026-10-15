-- ===================================================================
-- Copyright (C) 2026  Frédéric France         <frederic.france@free.fr>
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

ALTER TABLE llx_oauth_authcode ADD UNIQUE INDEX uk_oauth_authcode_code_hash (code_hash);
ALTER TABLE llx_oauth_authcode ADD INDEX idx_oauth_authcode_expire_at (expire_at);
ALTER TABLE llx_oauth_authcode ADD INDEX idx_oauth_authcode_fk_oauth_client (fk_oauth_client);
