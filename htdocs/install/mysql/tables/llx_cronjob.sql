-- ===================================================================
-- Copyright (C) 2013 Laurent Destailleur	<eldy@users.sourceforge.net>
-- Copyright (C) 2013 Florian Henry	<florian.henry@open-concept.pro>
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
-- ===================================================================


CREATE TABLE llx_cronjob 
(
	rowid 			integer AUTO_INCREMENT PRIMARY KEY,
	tms 			timestamp,
	datec 			datetime,
	jobtype			varchar(10) NOT NULL,
  	label 			text NOT NULL,
	command			varchar(255),
  	classesname 		varchar(255),
  	objectname		varchar(255),
  	methodename		varchar(255),
  	params 			text NOT NULL,
	md5params 		varchar(32),
  	module_name 		varchar(255),
  	priority 		integer DEFAULT 0,
  	datelastrun 		datetime,
  	datenextrun 		datetime,
  	datestart		datetime,
  	dateend			datetime,
  	datelastresult      	datetime,
  	lastresult      	text,
  	lastoutput      	text,
  	unitfrequency	 	integer NOT NULL DEFAULT 0,
  	frequency 		integer NOT NULL DEFAULT 0,
	nbrun			integer,
  	status 			integer NOT NULL DEFAULT 1,
  	fk_user_author 		integer DEFAULT NULL,
  	fk_user_mod 		integer DEFAULT NULL,
	note text,
	libname			varchar(255)
)ENGINE=innodb;
