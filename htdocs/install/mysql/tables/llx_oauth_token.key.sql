-- Copyright (C) 2026 Guenter Lukas      <gl@gl.co.at>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.


-- A passkey credential ID is globally unique: it is the key used to find the passkey at login (NULL for other services)
ALTER TABLE llx_oauth_token ADD UNIQUE INDEX uk_oauth_token_passkey_credential_id (passkey_credential_id);
