-- ===================================================================
-- Copyright (C) 2026		Jon Bendtsen          		<jon.bendtsen.github@jonb.dk>
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
-- Table: llx_budgetsheet_lines
-- Description: Detailed line items with liquidity tracking.
-- Features: Supports prorating (date_range) and cash-flow timing.
-- ===================================================================

CREATE TABLE llx_budgetsheet_lines (
   rowid integer AUTO_INCREMENT PRIMARY KEY,
   fk_budgetsheet       integer NOT NULL,

   label                varchar(255) NOT NULL,
   fk_c_type_of_operation integer,

   qty                  double(24,8) DEFAULT 1,
   unit_price_ht        double(24,8) DEFAULT 0,
   subprice_ttc         double(24,8) DEFAULT 0,
   value_unit           double(24,8) DEFAULT 0,
   remise_percent       double(24,8) DEFAULT 0,

   vat_src_code         varchar(10) DEFAULT '',
   tva_tx               double(7,4),
   localtax1_tx         double(7,4) DEFAULT 0,
   localtax1_type       varchar(10),
   localtax2_tx         double(7,4) DEFAULT 0,
   localtax2_type       varchar(10),

   total_ht             double(24,8) DEFAULT 0 NOT NULL,
   total_tva            double(24,8) DEFAULT 0 NOT NULL,
   total_localtax1      double(24,8) DEFAULT 0,
   total_localtax2      double(24,8) DEFAULT 0,
   total_ttc            double(24,8) DEFAULT 0 NOT NULL,

   date_start           date NOT NULL,
   date_end             date,
   date_payment_expected date NOT NULL,

   docnumber            varchar(128),
   info_bits            integer DEFAULT 0,
   special_code         integer DEFAULT 0,
   product_type         integer DEFAULT -1,
   comments             text,
   rule_warning_message text,
   rang                 integer DEFAULT 0,

   fk_multicurrency     integer,
   multicurrency_code   varchar(3),
   multicurrency_subprice double(24,8) DEFAULT 0,
   multicurrency_subprice_ttc double(24,8) DEFAULT 0,
   multicurrency_total_ht double(24,8) DEFAULT 0,
   multicurrency_total_tva double(24,8) DEFAULT 0,
   multicurrency_total_ttc double(24,8) DEFAULT 0,

   datec                datetime NOT NULL,
   tms                  timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
   fk_user_creat        integer DEFAULT NULL,
   fk_user_modif        integer DEFAULT NULL,
   import_key           varchar(14)
) ENGINE=innodb;
