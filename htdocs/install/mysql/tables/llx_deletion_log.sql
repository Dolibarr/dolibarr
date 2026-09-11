-- Copyright (C) 2026 Frédéric France      <frederic.france@free.fr>
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
-- Table to keep a short-lived trace of deleted objects (tombstones), so that a
-- page holding a partial view of a collection (e.g. an ajax-refreshed calendar)
-- can learn which records disappeared without reloading the whole set. Rows are
-- purged after MAIN_DELETION_LOG_RETENTION_DAYS days.


CREATE TABLE llx_deletion_log(
	rowid			integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	-- entity of the deleted object, so a consumer only sees the deletions of
	-- the company(ies) it is allowed to read (multi-company setups). It is part
	-- of the "deleted since" index and of the retention purge filter.
	entity			integer NOT NULL DEFAULT 1,
	element_type	varchar(64) NOT NULL,
	fk_object		integer NOT NULL,
	date_deletion	datetime NOT NULL,
	fk_user			integer NULL
) ENGINE=innodb;
