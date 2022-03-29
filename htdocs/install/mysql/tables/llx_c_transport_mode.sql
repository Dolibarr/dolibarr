-- ========================================================================
-- Copyright (C) 2019      Open-DSI			<support@open-dsi.fr>
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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ========================================================================

CREATE TABLE llx_c_transport_mode (
  rowid     integer AUTO_INCREMENT PRIMARY KEY,
  entity    integer	DEFAULT 1 NOT NULL,	-- multi company id
  code      varchar(3) NOT NULL,
  label     varchar(255) NOT NULL,
  active    tinyint DEFAULT 1  NOT NULL
) ENGINE=innodb;

