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

create table llx_ai_oauth_client
(
  rowid						integer AUTO_INCREMENT PRIMARY KEY,
  entity					integer DEFAULT 1 NOT NULL,
  client_id					varchar(255) NOT NULL,					-- Public identifier: a generated id, or the URL of the client's metadata document
  client_secret_hash		varchar(128),							-- sha256 of the secret, NULL for a public client (PKCE is then the only protection)
  client_name				varchar(255),							-- Name shown on the consent screen
  redirect_uris				text NOT NULL,							-- One absolute URI per line, matched exactly
  token_endpoint_auth_method varchar(32) DEFAULT 'none',			-- none, client_secret_basic or client_secret_post
  registered_from			varchar(64),							-- Address the registration came from, to bound self-registration
  datec						datetime NOT NULL,
  tms						timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)ENGINE=innodb;
