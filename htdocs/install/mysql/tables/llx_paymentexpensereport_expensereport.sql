-- ===================================================================
-- Copyright (C) 2024      Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2024      Gauthier VERDOL      <gauthier.verdol@atm-consulting.fr>
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

-- Table to manage the relation n-n between a payment of expense reports and all expense reports paid

create table llx_paymentexpensereport_expensereport
(
  rowid            		integer AUTO_INCREMENT PRIMARY KEY,
  fk_payment       		integer,
  fk_expensereport 		integer,
  amount           		double(24,8)     DEFAULT 0,

  multicurrency_code	varchar(3),
  multicurrency_tx		double(24,8) DEFAULT 1,
  multicurrency_amount	double(24,8) DEFAULT 0
)ENGINE=innodb;
