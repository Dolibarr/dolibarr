-- ========================================================================
-- Copyright (C) 2007 Regis Houssin <regis.houssin@capnetworks.com>
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
-- ========================================================================

create table llx_c_paper_format
(
  rowid    integer                          AUTO_INCREMENT PRIMARY KEY,
  code     varchar(16)                      NOT NULL,
  label    varchar(50)                      NOT NULL,
  width    float(6,2)                       DEFAULT 0,  -- Largeur du papier
  height   float(6,2)                       DEFAULT 0,  -- Hauteur du papier
  unit     varchar(5)                       NOT NULL,   -- Mesure unit
  active   tinyint DEFAULT 1                NOT NULL,
  module  varchar(32) NULL
)ENGINE=innodb;

-- 
-- Conversion
--
-- un inch = 2.54 cm
-- 1 point = 1cm * (72/2.54)
-- 1 point = 1mm * (72/25.4)
-- 1 point = 1in * 72