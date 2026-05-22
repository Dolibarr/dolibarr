-- ===================================================================
-- Copyright (C) 2026  Braito                  <braito4@hotmail.com>
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
-- ===================================================================

create table llx_emailcollector_ai_cleaning
(
  rowid                   integer AUTO_INCREMENT PRIMARY KEY,
  entity                  integer DEFAULT 1 NOT NULL,
  collector_id            integer,
  msgid                   varchar(255),
  raw_hash                varchar(80),
  clean_hash              varchar(80),
  clean_body              MEDIUMTEXT,
  cleaning_json           LONGTEXT,
  cleaning_confidence     double,
  cleaning_model          varchar(190),
  prompt_code             varchar(128),
  prompt_version          varchar(32),
  context_profile_code    varchar(128),
  context_profile_version varchar(32),
  handoff_payload_json    LONGTEXT,
  date_creation           datetime,
  tms                     timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)ENGINE=innodb;
