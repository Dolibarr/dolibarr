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
       commentaires text,
       mail_admin VARCHAR(128),
       nom_admin VARCHAR(64),
       titre TEXT,
       date_fin DATETIME,
       format VARCHAR(2),
       mailsonde varchar(2) DEFAULT '0',
       allow_comments TINYINT(1) unsigned NOT NULL DEFAULT 1,
	   allow_spy TINYINT(1) unsigned NOT NULL DEFAULT 1,
       tms TIMESTAMP,
	   sujet TEXT
) ENGINE=InnoDB;
