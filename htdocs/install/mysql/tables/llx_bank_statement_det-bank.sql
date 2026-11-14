-- ===================================================================
-- Copyright (C) 2024 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2026 Laurent MAGNIN       <laurent.magnin@evarisk.com>
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

-- Table to store statement lines / transactions of a bank statement (ISO 20022 camt.053 & MT940)

CREATE TABLE llx_bank_statement_det
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  fk_bank_statement         integer NOT NULL,
  bdate                     date NOT NULL,
  vdate                     date NOT NULL,
  amount                    double(24,8) NOT NULL,
  currency_code             varchar(3) DEFAULT 'EUR',
  charges_amount            double(24,8) DEFAULT 0,
  label                     varchar(255) NOT NULL,
  end_to_end_id             varchar(64) NULL,
  acct_svcr_ref             varchar(64) NULL,
  mandate_id                varchar(64) NULL,
  check_number              varchar(32) NULL,
  counterparty_name         varchar(255) NULL,
  counterparty_iban         varchar(34) NULL,
  counterparty_bic          varchar(11) NULL,
  remittance_unstructured   text NULL,
  remittance_structured     varchar(140) NULL,
  bank_tx_code              varchar(16) NULL,
  proprietary_code          varchar(32) NULL,
  record_type               varchar(64) NULL,
  fitid                     varchar(255) NULL,
  fk_soc                    integer NULL,
  fk_bank                   integer NULL,
  status                    smallint DEFAULT 0 NOT NULL,
  datas                     text NULL,
  datec                     datetime NULL,
  tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_author            integer NULL,
  fk_user_modif             integer NULL,
  import_key                varchar(14) NULL
) ENGINE=innodb;
