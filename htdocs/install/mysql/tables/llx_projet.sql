-- ===========================================================================
-- Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2010 Regis Houssin        <regis.houssin@capnetworks.com>
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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ===========================================================================

create table llx_projet
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc           integer,
  datec            date,						-- date creation project
  tms              timestamp,
  dateo            date,						-- date start project
  datee            date,						-- date end project
  ref              varchar(50),
  entity           integer DEFAULT 1 NOT NULL,	-- multi company id
  title            varchar(255) NOT NULL,
  description      text,
  fk_user_creat    integer NOT NULL,			-- createur du projet
  public           integer,						-- project is public or not
  fk_statut        integer DEFAULT 0 NOT NULL,	-- open or close
  fk_opp_status    integer DEFAULT NULL,	        -- if project is used to manage opportunities
  date_close       datetime DEFAULT NULL,    
  fk_user_close    integer DEFAULT NULL,
  note_private     text,
  note_public      text,
  --budget_days      real,                      -- budget in days is sum of field planned_workload of tasks
  opp_amount       double(24,8),
  budget_amount    double(24,8),				
  model_pdf        varchar(255)
)ENGINE=innodb;
