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
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
-- ============================================================================

CREATE TABLE llx_opensurvey_formquestions (
	rowid INTEGER AUTO_INCREMENT NOT NULL PRIMARY KEY,
	id_sondage VARCHAR(16),
	question TEXT,
	type VARCHAR(16) NOT NULL DEFAULT 'radio',			-- Type of answer: text, textarea, radio (one answer among the available answers), checkbox (several answers among the available answers)
	position INTEGER NOT NULL DEFAULT 0,				-- Order of the question into the survey
	mandatory TINYINT NOT NULL DEFAULT 0,				-- 1 = the respondent must answer
    available_answers TEXT								-- List of available answers (one per line). Not used for type text and textarea
) ENGINE=innodb;
