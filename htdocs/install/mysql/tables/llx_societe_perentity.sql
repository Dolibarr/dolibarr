-- ========================================================================
-- Copyright (C) 2021		Open-Dsi	<support@open-dsi.fr>
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

create table llx_societe_perentity
(
  rowid         			integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc        			integer,
  entity             		integer DEFAULT 1 NOT NULL,             -- multi company id
  accountancy_code_customer varchar(24),                         	-- customer accountancy auxiliary account
  accountancy_code_supplier varchar(24),                         	-- supplier accountancy auxiliary account
  accountancy_code_sell		varchar(32),                            -- Selling accountancy code
  accountancy_code_buy		varchar(32)                             -- Buying accountancy code
)ENGINE=innodb;
