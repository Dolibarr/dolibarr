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


CREATE TABLE llx_queue_failed_jobs
(
	rowid			integer AUTO_INCREMENT PRIMARY KEY,
	entity			integer NOT NULL DEFAULT 1,
	uuid			varchar(36) NOT NULL,
	queue			varchar(128) NOT NULL DEFAULT 'default',
	jobclass		varchar(255) NOT NULL,
	jobfile			varchar(255) NOT NULL,
	payload			mediumtext NOT NULL,
	exception		mediumtext,						-- message + stack trace of the last failure
	fk_user_author	integer DEFAULT NULL,
	failed_at		datetime NOT NULL,
	tms				timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)ENGINE=innodb;
