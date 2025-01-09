-- ============================================================================
-- Copyright (C) 2013 Laurent Destailleur <eldy@users.sourceforge.net>
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

CREATE TABLE llx_oauth_token (
    rowid 			integer AUTO_INCREMENT PRIMARY KEY,
    service 		varchar(36),         	-- What king of key or token: 'Google', 'Stripe', 'auth-public-key', ...
    token 			text,				 	-- token in serialize format, of an object StdOAuth2Token of library phpoauth2. Deprecated, use tokenstring instead.
    tokenstring 	text,				 	-- token in json or text format. Value depends on 'service'. For example for an OAUTH service: '{"access_token": "sk_test_cccc", "refresh_token": "rt_aaa", "token_type": "bearer", ..., "scope": "read_write"}
    state           text,                   -- the state (list of permission) the token was obtained for
    fk_soc 			integer,				-- Id of thirdparty in llx_societe
    fk_user 		integer,             	-- Id of user in llx_user
    fk_adherent 	integer,				-- Id of member in llx_adherent
    restricted_ips 	varchar(200), 			-- Restrict the authentication mode/token to some IPs
    datec       	datetime DEFAULT NULL,	-- date creation project
    tms             timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    entity integer DEFAULT 1
)ENGINE=innodb;
