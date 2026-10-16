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


-- A credential ID is globally unique: it is the key used to find the passkey at login
ALTER TABLE llx_user_passkey ADD UNIQUE INDEX uk_user_passkey_credential_id (credential_id);

ALTER TABLE llx_user_passkey ADD INDEX idx_user_passkey_fk_user (fk_user);

ALTER TABLE llx_user_passkey ADD CONSTRAINT fk_user_passkey_fk_user FOREIGN KEY (fk_user) REFERENCES llx_user (rowid);
