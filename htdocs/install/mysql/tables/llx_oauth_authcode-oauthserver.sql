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

-- Authorization codes of the OAuth 2.1 authorization server (module OAuth Server). Single use, 60 s.
CREATE TABLE llx_oauth_authcode
(
  rowid                   integer AUTO_INCREMENT PRIMARY KEY,
  entity                  integer DEFAULT 1 NOT NULL,
  code_hash               varchar(80) NOT NULL,            -- 'sha256:' + hex digest of the code
  fk_oauth_client         integer NOT NULL,
  fk_user                 integer NOT NULL,
  redirect_uri            text NOT NULL,
  scope                   varchar(255) NOT NULL,
  resource                varchar(255),                    -- RFC 8707
  code_challenge          varchar(128) NOT NULL,
  code_challenge_method   varchar(8) DEFAULT 'S256' NOT NULL,
  expire_at               datetime NOT NULL,
  date_used               datetime,                        -- set when the code is redeemed
  fk_oauth_token          integer,                         -- grant created when the code was redeemed
  datec                   datetime
)ENGINE=innodb;
