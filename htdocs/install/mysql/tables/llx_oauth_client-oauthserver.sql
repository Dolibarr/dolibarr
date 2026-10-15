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

-- Clients of the OAuth 2.1 authorization server (module OAuth Server)
CREATE TABLE llx_oauth_client
(
  rowid                       integer AUTO_INCREMENT PRIMARY KEY,
  entity                      integer DEFAULT 1 NOT NULL,
  client_id                   varchar(64) NOT NULL,          -- public identifier, random
  client_secret_hash          varchar(255),                  -- password_hash() of the secret, NULL for a public client
  label                       varchar(255) NOT NULL,
  redirect_uris               text NOT NULL,                 -- JSON array of exact redirect URIs
  allowed_scopes              varchar(255) NOT NULL,         -- space separated
  token_endpoint_auth_method  varchar(32) DEFAULT 'none' NOT NULL,  -- none, client_secret_basic, client_secret_post
  registration_type           varchar(16) DEFAULT 'admin' NOT NULL, -- admin or dynamic
  status                      smallint DEFAULT 1 NOT NULL,   -- 1 enabled, 0 disabled
  fk_user_creat               integer,
  datec                       datetime,
  tms                         timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  date_last_use               datetime
)ENGINE=innodb;
