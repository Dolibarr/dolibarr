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
       id_sondage_admin CHAR(24),
       commentaires text,
       mail_admin VARCHAR(128),
       nom_admin VARCHAR(64),
       titre text,
       date_fin datetime,
       format VARCHAR(2),
       mailsonde varchar(2) DEFAULT '0',
       survey_link_visible integer DEFAULT 1,
	   canedit integer DEFAULT 0,
       origin varchar(64),
       tms timestamp,
	   sujet TEXT
) ENGINE=InnoDB;
