-- SQL definition for module ticketsup
-- Copyright (C) 2013  Jean-François FERRY <hello@librethic.io>
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
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.

ALTER TABLE llx_ticketsup ADD UNIQUE uk_ticketsup_rowid_track_id (rowid, track_id);
ALTER TABLE llx_ticketsup ADD INDEX id_ticketsup_track_id (track_id);
