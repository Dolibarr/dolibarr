-- ============================================================================
-- Copyright (C) 2012 Mikael Carlavan     <mcarlavan@qis-network.com>
-- Copyright (C) 2017 ATM Consulting      <contact@atm-consulting.fr>
-- Copyright (C) 2017 Pierre-Henry Favre  <phf@atm-consulting.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
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

CREATE TABLE IF NOT EXISTS llx_c_exp_tax_cat (
    rowid       integer  AUTO_INCREMENT PRIMARY KEY,
    label       varchar(48) NOT NULL, 
    entity      integer DEFAULT 1 NOT NULL,
    active      integer DEFAULT 1 NOT NULL	          
)ENGINE=innodb;
