-- ========================================================================
-- Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2011      Regis Houssin        <regis.houssin@inodbox.com>
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
--
-- Table of events and actions (past and to do). 
-- This is also the table to track events on other Dolibarr objects.
-- ========================================================================

create table llx_actioncomm
(
  id				integer AUTO_INCREMENT PRIMARY KEY,
  ref               varchar(30) NOT NULL,
  ref_ext			varchar(255),					-- reference into an external system (not used by dolibarr). Example: An id coming from google calendar has length between 5 and 1024 chars. An event id must follow rule: chars used in base32hex encoding (i.e. lowercase letters a-v and digits 0-9, see section 3.1.2 in RFC2938)
  entity			integer DEFAULT 1 NOT NULL,		-- multi company id
  datep				datetime,						-- date start
  datep2			datetime,						-- date end

  fk_action			integer,						-- type of action (optional link with id in llx_c_actioncomm or null)
  code				varchar(50) NULL,				-- code of action for automatic action ('AC_OTH_AUTO' for automatic actions, 'AC_EMAILIN_AUTO' for email input, 'AC_xxx' for manual action...) 

  datec				datetime,						-- date creation
  tms				timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,						-- last modification date
  fk_user_author	integer,						-- user id of user that has created record
  fk_user_mod		integer,						-- user id of user that has modified record

  fk_project		integer,
  fk_soc			integer,
  fk_contact		integer,
  fk_parent			integer NOT NULL default 0,
  fk_user_action	integer,						-- user id of owner of action (note that users assigned to event are stored into table 'actioncomm_resources')
  fk_user_done		integer,						-- user id of user that has made action (deprecated)

  transparency      integer,						-- transparency (ical standard). used to say if user assigned to event are busy or not by event. This field may be deprecated if we want to store transparency for each assigned user, moved into table llx_actioncomm_resources.

  priority			smallint,						-- priority (ical standard)
  visibility		varchar(12) DEFAULT 'default',	-- visibility (ical standard) - 'default', 'public', 'private', 'confidential'
  fulldayevent		smallint NOT NULL default 0,    -- full day (ical standard)
  percent			smallint NOT NULL default 0,
  location			varchar(128),
  durationp			real,							-- planed duration

  label				varchar(255) NOT NULL,			-- label/title of event or topic of email
  note				mediumtext,						-- private note of event or content of email
  
  calling_duration  integer,                        -- when event is a phone call, duration of phone call
  
  email_subject		varchar(255),					-- when event was an email, we store here the subject. content is stored into note.
  email_msgid		varchar(255),					-- when event was an email, we store here the msgid
  email_from		varchar(255),					-- when event was an email, we store here the from
  email_sender		varchar(255),					-- when event was an email, we store here the sender
  email_to			varchar(255),					-- when event was an email, we store here the email_to
  email_tocc		varchar(255),					-- when event was an email, we store here the email_tocc
  email_tobcc		varchar(255),					-- when event was an email, we store here the email_tobcc
  errors_to			varchar(255),					-- when event was an email, we store here the erros_to
  reply_to			varchar(255),					-- when event was an email, we store here the reply_to
  
  recurid           varchar(128),                   -- used to store event id to link each other all the repeating event record. It can be the 'iCalUID' as in RFC5545 (an id similar for all the same serie)
  recurrule         varchar(128),					-- contains string with ical format recurring rule like 'FREQ=MONTHLY;INTERVAL=2;BYMONTHDAY=19' or 'FREQ=WEEKLY;BYDAY=MO'
  recurdateend      datetime,						-- no more recurring event after this date

  num_vote          integer DEFAULT NULL,          -- use for Event Organization module
  event_paid        smallint NOT NULL DEFAULT 0,    -- use for Event Organization module
  status            smallint NOT NULL DEFAULT 0,    -- use for Event Organization module for now, but could be use after for event global status

  fk_element		integer DEFAULT NULL,			-- For link to an element (proposal, invoice, order, ...)
  elementtype		varchar(255) DEFAULT NULL,		-- For link to an element (proposal, invoice, order, ...)

  ip            varchar(250),              --ip used to create record (for public submission page)

  fk_bookcal_calendar integer DEFAULT NULL, --fk_bookcal_calendar used to link booking to bookcal calendar

  import_key		varchar(14),
  extraparams		varchar(255)					-- for other parameters with json format
)ENGINE=innodb;




