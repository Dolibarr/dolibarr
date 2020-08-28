-- =============================================================================
-- Copyright (C) 2015-2016 Marcos García de La Fuente	<hola@marcosgdf.com>
-- Copyright (C) 2020      Alexandre Spangaro			<aspangaro@open-dsi.fr>
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
--
-- =============================================================================

create table llx_user_smtp
(
  fk_user		integer PRIMARY KEY NOT NULL,
  smtp_server	varchar(255),
  smtp_port		integer,
  smtp_tls		integer,
  smtp_starttls	integer,
  smtp_id		varchar(255),
  smtp_pw		varchar(255),
  imap_server	varchar(255),
  imap_port		integer,
  imap_tls		integer,
  imap_id		varchar(255),
  imap_pw		varchar(255),
  imap_folder	varchar(255)
)ENGINE=innodb;