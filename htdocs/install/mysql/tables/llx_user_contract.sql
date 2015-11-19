-- ===================================================================
-- Copyright (C) 2013-2015  Alexandre Spangaro <aspangaro.dolibarr@gmail.com>
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

CREATE TABLE llx_user_contract 
(
    rowid                   integer AUTO_INCREMENT PRIMARY KEY,
    entity                  integer DEFAULT 1 NOT NULL, -- multi company id
	fk_user                 integer NOT NULL,
	type_contract           integer NOT NULL,
	date_start_contract     date NOT NULL,
	date_end_contract       date NULL,	
	date_dpae               date NULL,
	date_medicalexam        date NULL,
	date_sign_employee      date NULL,
	date_sign_management    date NULL,
	description             VARCHAR(255),
	fk_user_author          integer,
	fk_user_modif           integer,
	datec                   datetime,
    tms                     timestamp
) 
ENGINE=innodb;
