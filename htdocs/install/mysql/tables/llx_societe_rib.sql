-- =============================================================================
-- Copyright (C) 2000-2004  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
-- Copyright (C) 2005-2009  Regis Houssin           <regis.houssin@inodbox.com>
-- Copyright (C) 2012       Juanjo Menent           <jmenent@2byte.es>
-- Copyright (C) 2013       Peter Fontaine          <contact@peterfontaine.fr>
-- Copyright (C) 2023       Alexandre Spangaro      <aspangaro@easya.solutions>
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
-- Table with the payment modes of a thirdparty (BAN, Paypal, Card, ...)
-- =============================================================================

create table llx_societe_rib
(
  rowid          integer AUTO_INCREMENT PRIMARY KEY,
  type           varchar(32) DEFAULT 'ban' NOT NULL,							-- 'ban' or 'paypal' or 'card' or 'stripe'
  label          varchar(200),
  fk_soc         integer NOT NULL,
  datec          datetime,
  tms            timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  -- For BAN
  bank           varchar(255),  -- bank name
  code_banque    varchar(128),  -- bank code
  code_guichet   varchar(6),    -- desk code
  number         varchar(255),  -- account number
  cle_rib        varchar(5),    -- key of bank account

  bic               varchar(20),    -- 11 according to ISO 9362 (we keep 20 for backward compatibility)
  bic_intermediate  varchar(11),    -- 11 according to ISO 9362. Same as bic but for intermediate bank
  iban_prefix       varchar(34),    -- full iban. 34 according to ISO 13616

  domiciliation  varchar(255),
  proprio        varchar(60),
  owner_address  varchar(255),
  default_rib    smallint NOT NULL DEFAULT 0,
  state_id       integer,
  fk_country     integer,
  currency_code  varchar(3),

  model_pdf		 varchar(255),					-- last template used to generate main document
  last_main_doc	 varchar(255),					-- relative filepath+filename of last main generated document

  -- For BAN direct debit feature
  rum           varchar(32),	 				-- RUM value to use for SEPA generation
  date_rum      date,							-- Date of mandate
  frstrecur     varchar(16) default 'FRST',     -- 'FRST' or 'RECUR'

  -- For credit card
  last_four         varchar(4),					-- last 4
  card_type         varchar(255),				-- card type 'VISA', 'MC' , ...
  cvn               varchar(255),
  exp_date_month    integer,
  exp_date_year     integer,
  country_code      varchar(10),

  -- For Paypal
  approved                          integer DEFAULT 0,
  email                             varchar(255),
  ending_date                       date,
  max_total_amount_of_all_payments  double(24,8),
  preapproval_key                   varchar(255),
  starting_date                     date,
  total_amount_of_all_payments      double(24,8),

  --For Stripe, Stancer, ...
  stripe_card_ref   varchar(128),               -- card_...'
  stripe_account    varchar(128),				-- 'pk_live_...'

  ext_payment_site  varchar(128),               -- name of external paymentmode (for example 'StripeLive')

  extraparams       varchar(255),               -- for other parameters with json format
  
  -- For Online Sign
  date_signature    datetime,
  online_sign_ip    varchar(48),
  online_sign_name  varchar(64),
  
  comment           varchar(255),
  ipaddress         varchar(68),
  status            integer NOT NULL DEFAULT 1, -- 1=ACTIVE, 0=IN_TRASH
  import_key        varchar(14)                 -- import key
)ENGINE=innodb;
