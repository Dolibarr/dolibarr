-- ===================================================================
-- Copyright (C) 2026 Michael Wallis
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

-- History of the changes of a loan: rate changes, and payments that differ from the schedule,
-- with how the unpaid payments were recalculated.
create table llx_loan_change
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  entity			integer DEFAULT 1 NOT NULL,
  fk_loan			integer NOT NULL,
  datec				datetime,
  tms				timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  date_change		date,							-- date from which the change applies
  reason			varchar(16) NOT NULL,			-- 'rate' (rate change) or 'payment' (payment different from the schedule)
  rate_old			double,
  rate_new			double,
  keep_mode			varchar(16),					-- 'term' (number of payments kept), 'payment' (repayment kept) or '' (no schedule)
  payment_old		double(24,8),
  payment_new		double(24,8),
  nbterm_old		real,
  nbterm_new		real,
  fk_payment_loan	integer DEFAULT NULL,			-- payment that caused the change (reason 'payment')
  fk_user_author	integer DEFAULT NULL
)ENGINE=innodb;
