-- Copyright (C) 2026 Laurent Magnin
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see <https://www.gnu.org/licenses/>.

create table llx_datapolicy_dpo
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  entity           integer DEFAULT 1 NOT NULL,
  dpo_type         varchar(16) DEFAULT 'user' NOT NULL,
  fk_user          integer DEFAULT NULL,
  fk_soc           integer DEFAULT NULL,
  fk_socpeople     integer DEFAULT NULL,
  lastname         varchar(128) NOT NULL,
  firstname        varchar(128) NOT NULL,
  job              varchar(128) DEFAULT NULL,
  email            varchar(255) NOT NULL,
  phone            varchar(64) NOT NULL,
  contact_form_url varchar(255) DEFAULT NULL,
  gdpr_prerequisites_validated     tinyint(1) DEFAULT 0 NOT NULL,
  gdpr_skills_certificate_file     varchar(255) DEFAULT NULL,
  gdpr_authority_declaration_file  varchar(255) DEFAULT NULL,
  date_start                       date NOT NULL,
  date_end                         date DEFAULT NULL,
  datec            datetime,
  tms              timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat    integer,
  fk_user_modif    integer
)ENGINE=innodb;
