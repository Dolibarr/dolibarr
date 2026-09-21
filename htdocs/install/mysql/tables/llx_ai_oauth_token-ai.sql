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

create table llx_ai_oauth_token
(
  rowid						integer AUTO_INCREMENT PRIMARY KEY,
  entity					integer DEFAULT 1 NOT NULL,
  token_type				varchar(8) NOT NULL,					-- code, access or refresh
  token_hash				varchar(64) NOT NULL,					-- sha256 of the value; the value itself is never stored
  fk_client					integer NOT NULL,						-- llx_ai_oauth_client.rowid
  fk_user					integer NOT NULL,						-- Dolibarr user who granted the consent
  scope						varchar(255),
  resource					varchar(255),							-- Audience the token was issued for (RFC 8707)
  code_challenge			varchar(128),							-- PKCE S256 challenge, on authorization codes only
  redirect_uri				text,									-- Bound to the code, replayed on exchange
  expires_at				datetime NOT NULL,
  revoked					smallint DEFAULT 0 NOT NULL,
  datec						datetime NOT NULL,
  tms						timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)ENGINE=innodb;
