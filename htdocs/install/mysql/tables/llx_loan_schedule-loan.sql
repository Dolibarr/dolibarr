-- ===================================================================
-- Copyright (C) 2014		Alexandre Spangaro   <aspangaro@open-dsi.fr>
-- Copyright (C) 2015       Frederic France      <frederic.france@free.fr>
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

create table llx_loan_schedule
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  fk_loan			integer,
  datec				datetime,         -- creation date
  tms				timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  datep				datetime,         -- payment date
  amount_capital	double(24,8) DEFAULT 0,
  amount_insurance	double(24,8) DEFAULT 0,
  amount_interest	double(24,8) DEFAULT 0,
  fk_typepayment	integer NOT NULL,
  num_payment		varchar(50),
  note_private      text,
  note_public       text,
  fk_bank			integer NOT NULL,
  fk_payment_loan     integer,
  fk_user_creat		integer,          -- creation user
  fk_user_modif		integer           -- last modification user
)ENGINE=innodb;
