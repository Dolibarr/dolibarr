-- ============================================================================
-- Copyright (C) 2015	Laurent Destailleur		<eldy@users.sourceforge.net>
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

create table llx_budget_lines
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  fk_budget     	integer NOT NULL,
  fk_project_ids	varchar(180) NOT NULL,		-- 'IDS:x,y' = List of project ids related to this budget. If budget is dedicated to projects not yet started, we recommand to create a project 'Projects to come'. 'FILTER:ref=*ABC' or 'FILTER:categid=123' = Can also be a dynamic rule to select projects.
  amount			double(24,8) NOT NULL,
  datec        		datetime,
  tms           	timestamp,
  fk_user_creat 	integer,
  fk_user_modif 	integer,
  import_key    	integer  
)ENGINE=innodb;
