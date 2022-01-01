-- ============================================================================
-- Copyright (C) 2016       Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- This table can be used to store employee working contracts
-- ===========================================================================

create table llx_user_employment
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  entity            integer DEFAULT 1 NOT NULL, -- multi company id
  ref				varchar(50),				-- reference
  ref_ext			varchar(50),				-- reference into an external system (not used by dolibarr)
  fk_user			integer,
  datec             datetime,
  tms               timestamp,
  fk_user_creat     integer,
  fk_user_modif     integer,
  job				varchar(128),				-- job position. may be a dictionary
  status            integer NOT NULL,			-- draft, active, closed
  salary			double(24,8),				-- last and current value stored into llx_user
  salaryextra		double(24,8),				-- last and current value stored into llx_user
  weeklyhours		double(16,8),				-- last and current value stored into llx_user
  dateemployment    date,						-- last and current value stored into llx_user
  dateemploymentend date						-- last and current value stored into llx_user
)ENGINE=innodb;

