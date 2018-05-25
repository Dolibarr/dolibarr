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

CREATE TABLE llx_ticketsup_msg
(
	rowid       integer AUTO_INCREMENT PRIMARY KEY,
	entity		integer DEFAULT 1,
	fk_track_id   varchar(128),
	fk_user_action	integer,
	datec datetime,
	message	text,
	private		integer DEFAULT 0
)ENGINE=innodb;
