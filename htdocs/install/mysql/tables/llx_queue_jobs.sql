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


CREATE TABLE llx_queue_jobs
(
	rowid			integer AUTO_INCREMENT PRIMARY KEY,
	entity			integer NOT NULL DEFAULT 1,
	uuid			varchar(36) NOT NULL,			-- unique job id, used for tracking and retry
	queue			varchar(128) NOT NULL DEFAULT 'default',
	jobclass		varchar(255) NOT NULL,			-- class name of the QueueableJob
	jobfile			varchar(255) NOT NULL,			-- class file path, relative to a Dolibarr document root
	payload			mediumtext NOT NULL,			-- JSON, scalar job data only (never a Dolibarr object)
	priority		integer NOT NULL DEFAULT 0,		-- lower value runs first
	attempts		integer NOT NULL DEFAULT 0,		-- number of times this job has been reserved
	max_tries		integer NOT NULL DEFAULT 1,
	job_timeout		integer NOT NULL DEFAULT 0,		-- max seconds for one run, 0 = no hard limit
	fk_user_author	integer DEFAULT NULL,			-- user the job runs as
	available_at	datetime NOT NULL,				-- not runnable before this date (delay / backoff)
	reserved_at		datetime DEFAULT NULL,			-- not null = currently reserved by a worker
	reserved_pid	integer DEFAULT NULL,			-- pid of the worker holding the reservation
	created_at		datetime NOT NULL,
	tms				timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)ENGINE=innodb;
