-- ========================================================================
-- Copyright (C) 2026 		    Jon Bendtsen        <jon.bendtsen.github@jonb.dk>
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
-- ========================================================================

CREATE TABLE IF NOT EXISTS llx_c_actioncomm_role (
  rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  label VARCHAR(128) NOT NULL,
  active TINYINT DEFAULT 1 NOT NULL,
  position INT DEFAULT 0,
  picto VARCHAR(48) DEFAULT NULL
) ENGINE=INNODB;
