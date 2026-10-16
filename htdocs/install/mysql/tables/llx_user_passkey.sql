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
--
-- Passkeys (WebAuthn public key credentials) registered by a user to log in
-- without password (authentication mode 'passkey' in $dolibarr_main_authentication).
-- Only the public key is stored, the private key never leaves the authenticator.


CREATE TABLE llx_user_passkey(
	rowid				integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	entity				integer NOT NULL DEFAULT 1,
	fk_user				integer NOT NULL,
	label				varchar(255) NULL,				-- name given by the user to recognize the passkey (e.g. "MacBook Touch ID")
	credential_id		varchar(255) NOT NULL,			-- credential ID, lower case hex encoded (base64url is case sensitive, so it can't be used with a case insensitive collation)
	user_handle			varchar(128) NOT NULL,			-- WebAuthn user.id sent to the authenticator, lower case hex encoded. Random value, same for all passkeys of a user (never the rowid or the login).
	public_key			text NOT NULL,					-- public key, PEM format
	sign_count			integer NOT NULL DEFAULT 0,		-- last signature counter returned by the authenticator (0 for synced passkeys)
	aaguid				varchar(36) NULL,				-- model of authenticator, as UUID
	backup_eligible		smallint NOT NULL DEFAULT 0,	-- 1 if the passkey can be synced to other devices
	backup_state		smallint NOT NULL DEFAULT 0,	-- 1 if the passkey is currently synced, updated at each login
	date_creation		datetime NOT NULL,
	date_last_use		datetime NULL,
	tms					timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	ip_last_use			varchar(250) NULL
) ENGINE=innodb;
