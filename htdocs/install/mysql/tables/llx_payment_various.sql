-- ===================================================================
-- Copyright (C) 2017-2026  Alexandre Spangaro  <alexandre@inovea-conseil.com>
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

create table llx_payment_various
(
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  ref                   varchar(30) NULL,           -- payment reference number (currently NULL because there is no numbering manager yet)
  num_payment           varchar(50),                -- num cheque or other
  label                 varchar(255),
  tms                   timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  datec                 datetime,                   -- create date
  datep                 date,                       -- payment date
  datev                 date,                       -- value date (this field should not be here, only into bank tables)
  sens                  smallint DEFAULT 0 NOT NULL,-- Sens of the operation: 0 for debit operation, 1 for credit operation
  amount                double(24,8) DEFAULT 0 NOT NULL,
  fk_typepayment        integer NOT NULL,
  accountancy_code      varchar(32),
  subledger_account     varchar(32),
  fk_projet             integer DEFAULT NULL,
  fk_soc                integer DEFAULT NULL,       -- optional link to a thirdparty (MAIN option BANK_VARIOUS_PAYMENT_USE_THIRDPARTY)
  entity                integer DEFAULT 1 NOT NULL,	-- multi company id
  note                  text,
  fk_bank               integer,
  fk_user_author        integer,                    -- user who are created this payment
  fk_user_modif         integer,                    -- user who are modified this payment
  import_key            varchar(14)                 -- import key
)ENGINE=innodb;
