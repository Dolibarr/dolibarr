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
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
-- ===================================================================


CREATE TABLE llx_cronjob 
(
	rowid 			integer AUTO_INCREMENT PRIMARY KEY,
	tms 			timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	datec 			datetime,
	jobtype			varchar(10) NOT NULL,		-- 'method', 'function' or 'command'
  	label 			varchar(255) NOT NULL,
	command			varchar(255),
  	classesname 	varchar(255),				-- when jobtype is 'method', name of the class file containing the method.
  	objectname		varchar(255),
  	methodename		varchar(255),				-- name of method or function
  	params 			text,
	md5params 		varchar(32),
  	module_name 	varchar(255),
  	priority 		integer DEFAULT 0,
  	datelastrun 	datetime,					-- date last run start (see datelastresult for end with a result)
  	datenextrun 	datetime,					-- job will be run if current date higher that this date
  	datestart		datetime,						-- before this date no jobs will be run
  	dateend			datetime,						-- after this date, no more jobs will be run
  	datelastresult  datetime,						-- date last run end
  	lastresult      text,
  	lastoutput      text,
  	unitfrequency	varchar(255) NOT NULL DEFAULT '3600',
  	frequency 		integer NOT NULL DEFAULT 0,
    maxrun          integer NOT NULL DEFAULT 0,		-- set this to 1 for a job queued for on run only
	nbrun			integer,						-- nb of run complete (failed or not)
    autodelete      integer DEFAULT 0,				-- 0=Job is kept unchanged once nbrun > maxrun or date > dateend, 2=Job must be archived (archive = status 2) once nbrun > maxrun or date > dateend 
  	status 			integer NOT NULL DEFAULT 1,		-- 0=disabled, 1=enabled, 2=archived
  	processing 		integer NOT NULL DEFAULT 0,		-- 1=process currently running
  	pid             integer,                        -- The cronjob PID, NULL if not in processing
  	test		    varchar(255) DEFAULT '1',
  	fk_user_author 	integer DEFAULT NULL,
  	fk_user_mod 	integer DEFAULT NULL,
    fk_mailing      integer DEFAULT NULL,		-- id of emailing if job was queued to send mass emailing
	note 			text,
	libname			varchar(255),				-- when jobtype is 'function', name of the library file containing the function.
	email_alert		varchar(128),				-- email for alert
	entity			integer DEFAULT 0
)ENGINE=innodb;


