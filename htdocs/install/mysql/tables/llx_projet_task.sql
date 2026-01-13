-- ===========================================================================
-- Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2010 Regis Houssin        <regis.houssin@inodbox.com>
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

create table llx_projet_task
(
  rowid					integer AUTO_INCREMENT PRIMARY KEY,
  ref 					varchar(50),
  entity				integer DEFAULT 1 NOT NULL,	-- multi company id
  fk_projet				integer NOT NULL,
  fk_task_parent		integer DEFAULT 0 NOT NULL,
  datec					datetime,						-- date creation
  tms					timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,						-- last modification date
  dateo					datetime,						-- date start task
  datee					datetime,						-- date end task
  datev					datetime,						-- date validation
  label					varchar(255) NOT NULL,
  description			text,
  duration_effective	real DEFAULT 0,					-- DENORMALIZED FIELD. total of time spent on the task. total of lines into llx_element_time
  planned_workload		real DEFAULT 0,
  progress				integer DEFAULT 0,				-- percentage increase
  priority				integer DEFAULT 0,				-- priority
  budget_amount         double(24,8),
  fk_user_creat			integer,						-- user who created the task
  fk_user_modif			integer,						-- user who modify the task
  fk_user_valid			integer,						-- user who validated the task
  fk_statut				smallint DEFAULT 0 NOT NULL,    -- status of task
  note_private			text,
  note_public			text,
  rang                  integer DEFAULT 0,
  model_pdf        		varchar(255),
  import_key			varchar(14),						-- Import key,
  billable    smallint DEFAULT 1
)ENGINE=innodb;
