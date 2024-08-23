-- ========================================================================
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

  datestart						date,
  dateend						date,
  nbterm						real,
  rate							double  NOT NULL,

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
)ENGINE=innodb DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
