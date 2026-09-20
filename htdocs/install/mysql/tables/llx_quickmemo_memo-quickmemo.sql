-- Copyright (C) 2026		John BOTELLA
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
-- along with this program.  If not, see https://www.gnu.org/licenses/.


CREATE TABLE llx_quickmemo_memo(
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
    entity integer DEFAULT 1 NOT NULL,

    fk_element integer NOT NULL,
    element_type varchar(64) NOT NULL,

    quick_note text,
	date_creation datetime NOT NULL,
    tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_creat integer NOT NULL,
	fk_user_modif integer,
	fk_user_archived integer,
    date_archived timestamp DEFAULT NULL,

	import_key varchar(14),

    pos_z integer NOT NULL DEFAULT 0,
    pos_y integer NOT NULL DEFAULT 0,
    pos_x integer NOT NULL DEFAULT 0,
    pos_w integer NOT NULL DEFAULT 0,
    pos_h integer NOT NULL DEFAULT 0,

    color varchar(10),

    context_tab varchar(64),
    private tinyint NOT NULL DEFAULT 1,
    private_tpl tinyint NULL DEFAULT 0,
    rank_tpl integer DEFAULT 0,
    name_tpl varchar(256),
    shared_on_element integer NOT NULL DEFAULT 0,
    status integer NOT NULL DEFAULT 1
) ENGINE=innodb;
