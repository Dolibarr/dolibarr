-- ============================================================================
-- Copyright (C) 2024       Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2023       Florian Henry        <florian.henry@scopen.fr>
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
-- ============================================================================

CREATE TABLE llx_product_thirdparty
(
    rowid                               integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
    fk_product                          integer NOT NULL,
    fk_soc                              integer NOT NULL,
    fk_product_thirdparty_relation_type integer NOT NULL,
    date_start                          datetime,
    date_end                            datetime,
    fk_project                          integer,
    description                         text,
    note_public                         text,
    note_private                        text,
    date_creation                       datetime NOT NULL,
    tms                                 timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_creat                       integer NOT NULL,
    fk_user_modif                       integer,
    last_main_doc                       varchar(255),
    import_key                          varchar(14),
    model_pdf                           varchar(255),
    status                              integer DEFAULT 1 NOT NULL
)ENGINE=innodb;
