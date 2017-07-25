-- ============================================================================
-- Copyright (C) 2013 Laurent Destailleur <eldy@users.sourceforge.net>
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
-- ============================================================================

CREATE TABLE llx_opensurvey_sondage (
	id_sondage VARCHAR(16) PRIMARY KEY,
	entity integer DEFAULT 1 NOT NULL,	         -- multi company id
    commentaires text,
	mail_admin VARCHAR(128),
	nom_admin VARCHAR(64),
	fk_user_creat integer NOT NULL,
	titre TEXT NOT NULL,
	date_fin DATETIME NULL,
    status integer DEFAULT 1,
	format VARCHAR(2) NOT NULL,                 -- 'A' = Text choice (choices are saved into sujet field), 'D' = Date choice (choices are saved into sujet field), 'F' = Form survey
	mailsonde tinyint NOT NULL DEFAULT 0,
	allow_comments tinyint NOT NULL DEFAULT 1,
	allow_spy tinyint NOT NULL DEFAULT 1,
	tms TIMESTAMP,
	sujet TEXT									-- Not filled if format = 'F'. Question are into table llx_opensurvey_formquestions
) ENGINE=InnoDB;
