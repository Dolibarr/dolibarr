-- ===================================================================
-- Copyright (C) 2012      Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- ===================================================================

CREATE TABLE llx_holiday_logs 
(
rowid             integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
date_action       DATETIME NOT NULL,
fk_user_action    integer NOT NULL,
fk_user_update    integer NOT NULL,
fk_type           integer NOT NULL,
type_action       VARCHAR( 255 ) NOT NULL,
prev_solde        VARCHAR( 255 ) NOT NULL,
new_solde         VARCHAR( 255 ) NOT NULL
) 
ENGINE=innodb;
