-- ========================================================================
-- Copyright (C) 2014		Alexandre Spangaro   <aspangaro@open-dsi.fr>
-- Copyright (C) 2015       Frédéric France      <frederic.france@free.fr>
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

create table llx_loan
(
  rowid							integer AUTO_INCREMENT PRIMARY KEY,
  entity						integer DEFAULT 1 NOT NULL,
  datec							datetime,
  tms							timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  label							varchar(80) NOT NULL,
  fk_bank						integer,

  capital						double(24,8) DEFAULT 0 NOT NULL,
  insurance_amount				double(24,8) DEFAULT 0,
  balloon_amount				double(24,8) DEFAULT 0 NOT NULL,	-- balloon / residual: lump sum paid with the last payment

  datestart						date,
  dateend						date,
  nbterm						real,
  rate							double  NOT NULL,
  frequency						integer DEFAULT 12 NOT NULL,	-- number of payments per year: 52, 26, 12, 4, 2 or 1
  interest_basis				smallint DEFAULT 0 NOT NULL,	-- 0 = rate / payments per year, 1 = daily (rate x days in the period / 365)

  note_private					text,
  note_public					text,

  capital_position				double(24,8) DEFAULT 0,		-- If not a new loan, just have the position of capital
  date_position					date,

  paid							smallint default 0 NOT NULL,

  accountancy_account_capital	varchar(32),
  accountancy_account_insurance	varchar(32),
  accountancy_account_interest	varchar(32),

  fk_projet						integer DEFAULT NULL,

  fk_user_author				integer DEFAULT NULL,
  fk_user_modif					integer DEFAULT NULL,
  active						tinyint DEFAULT 1  NOT NULL
)ENGINE=innodb;
