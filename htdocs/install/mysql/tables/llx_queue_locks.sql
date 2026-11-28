-- ===================================================================
-- Copyright (C) 2026 Eric Seigne	<eric.seigne@cap-rel.fr>
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
-- ===================================================================


CREATE TABLE llx_queue_locks
(
	rowid			integer AUTO_INCREMENT PRIMARY KEY,
	name			varchar(128) NOT NULL,			-- runner or lock name, e.g. afterresponse, cronworker:web1
	owner			varchar(128) DEFAULT NULL,		-- hostname:pid currently holding the lock
	locked_until	datetime DEFAULT NULL,			-- lock expiry, null or past = free
	last_seen_at	datetime DEFAULT NULL,			-- heartbeat of the last run
	last_jobs		integer NOT NULL DEFAULT 0,		-- jobs processed during the last run
	last_error		text,							-- last error message of this runner, if any
	tms				timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)ENGINE=innodb;
