-- ========================================================================
-- Copyright (C) 2016 Frederic France  <frederic.france@free.fr>
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
-- ========================================================================

CREATE TABLE llx_c_format_cards
(
  rowid integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code varchar(50) NOT NULL,
  name varchar(50) NOT NULL,
  paper_size varchar(20) NOT NULL,
  orientation varchar(1) NOT NULL,
  metric varchar(5) NOT NULL,
  leftmargin double(24,8) NOT NULL,
  topmargin double(24,8) NOT NULL,
  nx integer NOT NULL,
  ny integer NOT NULL,
  spacex double(24,8) NOT NULL,
  spacey double(24,8) NOT NULL,
  width double(24,8) NOT NULL,
  height double(24,8) NOT NULL,
  font_size integer NOT NULL,
  custom_x double(24,8) NOT NULL,
  custom_y double(24,8) NOT NULL,
  active integer NOT NULL
) ENGINE=innodb;
