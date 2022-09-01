-- ============================================================================
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
-- ============================================================================
-- Table used for relations between elements of different types:
-- invoice-propal, propal-order, etc...
-- ============================================================================

CREATE TABLE llx_element_properties
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  element_type      varchar(64) NOT NULL,
  element           varchar(32) NOT NULL,
  module_name       varchar(31) NOT NULL,
  class_dir         text NOT NULL,
  class_file        varchar(128) NOT NULL,
  class_name        varchar(128) NOT NULL,
  datec             datetime,
  tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=innodb;

