-- ===========================================================================
-- Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2012-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2009      Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ===========================================================================

create table llx_facture_rec
(
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  titre              varchar(100) NOT NULL,
  entity             integer DEFAULT 1 NOT NULL,	 -- multi company id
  fk_soc             integer NOT NULL,
  datec              datetime,            -- date de creation
  tms				 timestamp,           -- date creation/modification

  suspended          integer DEFAULT 0,					-- 1=suspended
  
  amount             double(24,8)     DEFAULT 0 NOT NULL,
  remise             real     DEFAULT 0,
  remise_percent     real     DEFAULT 0,
  remise_absolue     real     DEFAULT 0,
  
  vat_src_code		 varchar(10)  DEFAULT '',			-- Vat code used as source of vat fields. Not strict foreign key here.
  tva                double(24,8)     DEFAULT 0,
  localtax1			 double(24,8)     DEFAULT 0,           -- amount localtax1
  localtax2          double(24,8)     DEFAULT 0,           -- amount localtax2
  revenuestamp       double(24,8)     DEFAULT 0,			 -- amount total revenuestamp
  total              double(24,8)     DEFAULT 0,
  total_ttc          double(24,8)     DEFAULT 0,

  fk_user_author     integer,             -- user creating
  fk_user_modif      integer,             -- user making last change
  
  fk_projet          integer,             -- projet auquel est associe la facture
  
  fk_cond_reglement  integer DEFAULT 0,  -- condition de reglement
  fk_mode_reglement  integer DEFAULT 0,  -- mode de reglement (Virement, Prelevement)
  date_lim_reglement date,				   -- date limite de reglement
  fk_account         integer,			  -- bank account id
  note_private       text,
  note_public        text,
  modelpdf           varchar(255),

  fk_multicurrency          integer,
  multicurrency_code        varchar(255),
  multicurrency_tx          double(24,8) DEFAULT 1,
  multicurrency_total_ht    double(24,8) DEFAULT 0,
  multicurrency_total_tva   double(24,8) DEFAULT 0,
  multicurrency_total_ttc   double(24,8) DEFAULT 0,

  usenewprice        integer DEFAULT 0,			-- update invoice with current price of product instead of recorded price
  frequency          integer,						-- frequency (for example: 3 for every 3 month)
  unit_frequency     varchar(2) DEFAULT 'm',		-- 'm' for month (date_when must be a day <= 28), 'y' for year, ... 
  
  date_when          datetime DEFAULT NULL,		-- date for next gen (when an invoice is generated, this field must be updated with next date)
  date_last_gen      datetime DEFAULT NULL,		-- date for last gen (date with last successfull generation of invoice)
  nb_gen_done        integer DEFAULT NULL,		-- nb of generation done (when an invoice is generated, this field must incremented)
  nb_gen_max         integer DEFAULT NULL,		    -- maximum number of generation
  auto_validate      integer DEFAULT 0,		-- 0 to create in draft, 1 to create and validate the new invoice
  generate_pdf       integer DEFAULT 1      -- 0 disable pdf, 1 to generate pdf
)ENGINE=innodb;
