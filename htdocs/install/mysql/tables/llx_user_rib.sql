-- =============================================================================
-- Copyright (C) 2015-2023  Alexandre Spangaro  <aspangaro@easya.solutions>
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
-- =============================================================================

create table llx_user_rib
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  fk_user           integer      NOT NULL,
  entity            integer DEFAULT 1 NOT NULL,	-- multi company id
  datec             datetime,
  tms               timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  label             varchar(30),
  bank              varchar(255),  -- bank name
  code_banque       varchar(128),  -- bank code
  code_guichet      varchar(6),    -- desk code
  number            varchar(255),  -- account number
  cle_rib           varchar(5),    -- key of bank account
  bic               varchar(11),   -- 11 according to ISO 9362
  bic_intermediate  varchar(11),   -- 11 according to ISO 9362. Same as bic but for intermediate bank
  iban_prefix       varchar(80),   -- full iban. 34 according to ISO 13616 but we set 80 to allow to store it with encryption information
  domiciliation     varchar(255),
  proprio           varchar(60),
  owner_address     varchar(255),
  state_id          integer,
  fk_country        integer,
  currency_code     varchar(3),
  default_rib       smallint DEFAULT 0 NOT NULL
)ENGINE=innodb;
