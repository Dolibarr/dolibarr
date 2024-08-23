-- ===========================================================================
-- Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

create table llx_projet
(
  rowid            	integer AUTO_INCREMENT PRIMARY KEY,
  fk_project        integer DEFAULT NULL,       -- parent project rowid
  fk_soc           	integer,
  datec            	datetime,					-- date creation project
  tms              	timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  dateo            	date,						-- date start project
  datee            	date,						-- date end project
  ref              	varchar(50),				-- reference number
  ref_ext          	varchar(50),				-- reference into an external system (not used by dolibarr)
  entity           	integer DEFAULT 1 NOT NULL,	-- multi company id
  title            	varchar(255) NOT NULL,
  description      	text,
  fk_user_creat    	integer NOT NULL,			-- createur du projet
  fk_user_modif    	integer,
  public           	integer,						-- project is public or not
  fk_statut        	integer DEFAULT 0 NOT NULL,	-- open or close
  fk_opp_status    	integer DEFAULT NULL,	        -- if project is used to manage opportunities
  opp_percent	   	double(5,2),
  fk_opp_status_end	integer DEFAULT NULL,	        -- if project is used to manage opportunities (the opportunity status the project has when set to lose)
  date_close       	datetime DEFAULT NULL,
  fk_user_close    	integer DEFAULT NULL,
  note_private     	text,
  note_public      	text,
  email_msgid      	varchar(175),				-- if project or lead is created by email collector, we store here MSG ID. Do not use a too large value, it generates trouble with unique index
  email_date 		datetime,					-- if project or lead is created by email collector, we store here Date of message
  --budget_days     real,                       -- budget in days is sum of field planned_workload of tasks
  opp_amount       	double(24,8),
  budget_amount    	double(24,8),
  usage_opportunity    integer DEFAULT 0,		-- Set to 1 if project is used to follow an opportunity
  usage_task           integer DEFAULT 1,		-- Set to 1 if project is used to manage tasks and/or record timesheet
  usage_bill_time      integer DEFAULT 0,		-- Set to 1 if time spent must be converted into invoices
  usage_organize_event integer DEFAULT 0,		-- Set to 1 if you want to use project to organize an event or receive attendees registration
  date_start_event 	datetime,					-- date start event
  date_end_event   	datetime,					-- date end event
  location         	varchar(255),				-- location
  accept_conference_suggestions integer DEFAULT 0,		-- Set to 1 if you want to allow unknown people to suggest conferences
  accept_booth_suggestions integer DEFAULT 0,	-- Set to 1 if you want to Allow unknown people to suggest booth
  max_attendees     integer DEFAULT 0,
  price_registration    double(24,8),
  price_booth    	double(24,8),
  model_pdf        	varchar(255),
  ip            	varchar(250),              		-- ip used to create record (for public submission page)
  last_main_doc	   	varchar(255),				-- relative filepath+filename of last main generated document
  import_key	   	varchar(14),				-- Import key
  extraparams		varchar(255)				-- to save other parameters with json format
)ENGINE=innodb DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
