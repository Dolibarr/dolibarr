-- ===================================================================
-- Copyright (C) 2026		Jon Bendtsen          		<jon.bendtsen.github@jonb.dk>
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

CREATE TABLE llx_budgetsheet (
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  ref                varchar(128) NOT NULL,
  entity             integer DEFAULT 1 NOT NULL,

  label              varchar(255) NOT NULL,

  fk_project         integer DEFAULT NULL,
  fk_task            integer DEFAULT NULL, 

  fk_status          integer DEFAULT 0,

  date_start         date,
  date_end           date,

  total_ht           double(24,8) DEFAULT 0,
  total_tva          double(24,8) DEFAULT 0,
  localtax1          double(24,8) DEFAULT 0,
  localtax2          double(24,8) DEFAULT 0,
  total_ttc          double(24,8) DEFAULT 0,

  note_public        text,
  note_private       text,

  date_create        datetime NOT NULL,
  date_valid         datetime,
  date_approve       datetime,
  date_cancel        datetime,
  tms                timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  fk_user_author     integer NOT NULL,
  fk_user_creat      integer DEFAULT NULL,
  fk_user_modif      integer DEFAULT NULL,
  fk_user_valid      integer DEFAULT NULL,
  fk_user_approve    integer DEFAULT NULL,
  fk_user_cancel     integer DEFAULT NULL,

  import_key         varchar(14),
  extraparams        varchar(255)
) ENGINE=innodb;
