-- ===========================================================================
-- Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
-- ===========================================================================

create table llx_projet_task_time
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  fk_task          integer NOT NULL,
  task_date        date,					-- only the day
  task_datehour    datetime,				-- day + hour
  task_date_withhour integer DEFAULT 0,	-- 0 by default, 1 if date was entered with start hour
  task_duration    double,
  fk_product       integer NULL,
  fk_user          integer,
  thm			   double(24,8),
  invoice_id       integer DEFAULT NULL,				-- If we need to invoice each line of timespent, we can save invoice id here
  invoice_line_id  integer DEFAULT NULL,                -- If we need to invoice each line of timespent, we can save invoice line id here
  intervention_id       integer DEFAULT NULL,				-- If we need to have an intervention line for each line of timespent, we can save intervention id here
  intervention_line_id  integer DEFAULT NULL,               -- If we need to have an intervention line of timespent line, we can save intervention line id here
  import_key	   varchar(14),					-- Import key
  datec            datetime,					-- date creation time
  tms              timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,					-- last modification date
  note             text							-- A comment
)ENGINE=innodb;
