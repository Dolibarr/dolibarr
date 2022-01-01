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

CREATE TABLE IF NOT EXISTS llx_expensereport_ik (
    rowid           integer  AUTO_INCREMENT PRIMARY KEY,
    datec           datetime  DEFAULT NULL,
    tms             timestamp,
    fk_c_exp_tax_cat integer DEFAULT 0 NOT NULL,
    fk_range        integer DEFAULT 0 NOT NULL,	  	  
    coef            double DEFAULT 0 NOT NULL,  
    ikoffset          double DEFAULT 0 NOT NULL,
    active          integer DEFAULT 1         
)ENGINE=innodb;
