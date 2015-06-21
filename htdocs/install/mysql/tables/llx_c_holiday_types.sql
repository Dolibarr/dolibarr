-- ===================================================================
-- Copyright (C) 2014-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
--
-- ===================================================================

CREATE TABLE llx_c_holiday_types (
  rowid integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code varchar(16) NOT NULL,
  label varchar(255) NOT NULL,
  affect integer NOT NULL,						-- a request will change sold or not
  delay integer NOT NULL,						-- Minimum delay to be allowed to make request
  newByMonth double(8,5) DEFAULT 0 NOT NULL, -- Amount of new days for each user each month
  fk_country integer DEFAULT NULL,			-- This type is dedicated to a country
  active integer DEFAULT 1
) ENGINE=innodb;
