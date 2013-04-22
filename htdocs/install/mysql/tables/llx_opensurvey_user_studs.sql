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

CREATE TABLE llx_opensurvey_user_studs (
    id_users INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(64) NOT NULL,
    id_sondage VARCHAR(16) NOT NULL,
    reponses VARCHAR(100) NOT NULL,
    tms timestamp
) ENGINE=InnoDB;
