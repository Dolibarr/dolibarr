-- Copyright (C) 2026		Lilian Sonzogni			<lsonzogni@ircf.fr>
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
--
-- History of the successive versions of the content of a knowledge record.
-- One row per past version: the row keeps the author and the date of the version it holds, not
-- those of the change that replaced it. The current version is the record itself.


CREATE TABLE llx_knowledgemanagement_knowledgerecord_log(
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	entity integer DEFAULT 1 NOT NULL,			-- multi company id
	fk_knowledgerecord integer NOT NULL,
	version integer NOT NULL,					-- 1 for the first content of the record
	date_version datetime,						-- when this content was written
	fk_user integer,							-- who wrote this content
	change_reason varchar(255),					-- why this content was written
	question text,
	answer longtext,
	lang varchar(6),
	status integer
) ENGINE=innodb;
