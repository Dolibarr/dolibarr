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
-- Table to keep a short-lived trace of deleted agenda events (tombstones), so
-- that a page holding a partial view of the agenda (e.g. an ajax-refreshed
-- calendar) can learn which events disappeared without reloading the whole
-- set. Rows are purged after MAIN_DELETION_LOG_RETENTION_DAYS days.
-- Scoped to llx_actioncomm only: a generic element_type/fk_object pair was
-- dropped in favor of fk_actioncomm + a copy of the event's uid, so a
-- consumer that only knows the uid (e.g. an external calendar sync) can also
-- learn about the deletion once the row itself is gone.
-- fk_user_action and assigned_users are a snapshot of the deleted event's
-- owner and assigned users (llx_actioncomm_resources, element_type='user'),
-- since that access-right info is no longer queryable once the row is gone.
-- A consumer must only be told about a deletion if it was allowed to read
-- the event, the same way agenda/myactions vs agenda/allactions works.


CREATE TABLE llx_deletion_log(
	rowid			integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	-- entity of the deleted event, so a consumer only sees the deletions of
	-- the company(ies) it is allowed to read (multi-company setups). It is part
	-- of the "deleted since" index and of the retention purge filter.
	entity			integer NOT NULL DEFAULT 1,
	fk_actioncomm	integer NOT NULL,
	uid				varchar(36) NULL,				-- copy of llx_actioncomm.uid (UUID) at deletion time (kept for consumers that only know the uid). Not unique here: this is an append-only log, not the source of truth for uid uniqueness (that constraint lives on llx_actioncomm.uid).
	fk_user_action	integer NULL,					-- copy of llx_actioncomm.fk_user_action (owner of the deleted event) at deletion time
	assigned_users	varchar(255) NULL,				-- comma separated list of user ids assigned to the deleted event at deletion time (llx_actioncomm_resources, element_type='user')
	date_deletion	datetime NOT NULL,
	fk_user			integer NULL					-- user who performed the deletion (not to be confused with fk_user_action, the event's owner)
) ENGINE=innodb;
