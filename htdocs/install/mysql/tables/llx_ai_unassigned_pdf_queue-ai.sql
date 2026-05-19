-- ===================================================================
-- Copyright (C) 2026
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

create table llx_ai_unassigned_pdf_queue
(
  rowid                integer AUTO_INCREMENT PRIMARY KEY,
  entity               integer DEFAULT 1 NOT NULL,
  status               varchar(32) DEFAULT 'queued' NOT NULL,
  priority             integer DEFAULT 50,
  source               varchar(32) DEFAULT 'emailcollector' NOT NULL,
  collector_id         integer,
  message_id           varchar(255),
  email_date           varchar(190),
  email_from           varchar(255),
  email_subject        varchar(255),
  attachment_name      varchar(255),
  attachment_relpath   varchar(1024),
  attachment_sha256    varchar(80),
  detected_doc_type    varchar(32) DEFAULT 'unknown',
  extraction_json      LONGTEXT,
  matching_json        LONGTEXT,
  proposed_object_type varchar(64),
  proposed_object_id   integer,
  confidence           double,
  needs_human_review   integer DEFAULT 1,
  review_note          varchar(255),
  attempts             integer DEFAULT 0,
  last_error           varchar(255),
  fk_user_review       integer,
  date_review          datetime,
  datec                datetime,
  tms                  timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)ENGINE=innodb;
