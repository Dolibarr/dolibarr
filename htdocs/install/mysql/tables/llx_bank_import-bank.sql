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

-- Table to receive manual import of a bank statement
-- Try to match compatibility with external module banking to capitalize on knowledge but removed fields for advanced features.
-- A line can come from an imported statement (fk_bank_record_import), be a line of the statement it has been reconciled
-- into (fk_bank_record_statement), or both. Bank entries made from a line are linked with llx_bank_url (type bank_import).
-- @todo Rename table into llx_bank_statement_det

CREATE TABLE llx_bank_import
(
  rowid                 integer         AUTO_INCREMENT PRIMARY KEY,
  fk_bank_record_import		integer		NULL,					-- imported statement (llx_bank_record) the line comes from
  fk_bank_record_statement	integer		NULL,					-- statement (llx_bank_record) the line has been reconciled into
  id_account			integer			NOT NULL,              	-- bank account ID in Dolibarr
  fitid					varchar(255)	NULL,              		-- OFX Financial Institution Transaction ID "FITID"
  record_type 			varchar(64)   	NULL,                  	-- OFX Type of transaction: DIRECTDEBIT, XFER, OTHER or code/type of operation
  label         		varchar(255)  	NOT NULL,               -- label of operation
  record_type_origin  	varchar(255)  	NULL,                   -- operation code/type origin
  label_origin  		varchar(255)  	NULL,                   -- label of operation origin
  comment				text			NULL,                   -- Comment/Motif
  note				    text			NULL,                   -- Notes like "References"
  end_to_end_id			varchar(64)		NULL,					-- ISO 20022 EndToEndId
  acct_svcr_ref			varchar(64)		NULL,					-- ISO 20022 AcctSvcrRef, reference of the transaction given by the bank
  mandate_id			varchar(64)		NULL,					-- ISO 20022 MndtId, SEPA direct debit mandate
  check_number			varchar(32)		NULL,					-- ISO 20022 ChqNb
  counterparty_name		varchar(255)	NULL,					-- ISO 20022 Dbtr/Nm or Cdtr/Nm
  counterparty_iban		varchar(34)		NULL,
  counterparty_bic		varchar(11)		NULL,
  remittance_structured	varchar(140)	NULL,					-- ISO 20022 Strd, structured creditor reference (RF ISO 11649)
  bank_tx_code			varchar(16)		NULL,					-- ISO 20022 bank transaction code (ex: PMNT-ICDT-ESCT)
  proprietary_code		varchar(32)		NULL,					-- national or proprietary operation code (ex: CFONB)
  bdate					date			NULL,                   -- date operation
  vdate					date			NULL,                   -- date value
  date_scraped			datetime		NULL,                  	-- date discarded
  original_amount		double(24,8)	NULL,                	-- OFX amount
  original_currency		varchar(255)	NULL,              		-- OFX Currency
  amount_debit			double(24,8)	NOT NULL,          		-- money spent. For statement using debit/credit. For statement using 1 amount, use original_amount.
  amount_credit       	double(24,8)  NOT NULL,          		-- money received. For statement using debit/credit. For statement using 1 amount, use original_amount.
  balance				double(24,8)	NULL,					-- running balance printed on the statement line (to check each line up to the closing balance)
  charges_amount		double(24,8)	NULL,					-- charges and fees of the line (ISO 20022 Chrgs/Amt)
  deleted_date			datetime		NULL,                  	-- to flag this record as deleted
  fk_duplicate_of		integer			NULL,                  	-- to flag this record as a duplicate of another one
  fk_soc				integer			NULL,					-- thirdparty recognized for the line
  fk_bank				integer			NULL,					-- main bank entry (llx_bank) made from the line. All of them are into llx_bank_url.
  status				smallint		NOT NULL,               -- 0=just imported (to process), 1=recorded, 2=excluded
  datec					datetime		NOT NULL,		        -- date creation
  tms					timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,	-- date of last modification
  fk_user_author	    integer         NOT NULL, 		   		-- user who created the record
  fk_user_modif		    integer,					            -- user who modified the record
  import_key			varchar(14),					        -- import key
  datas					text			NULL                    -- full record/line coming from source (NULL for a line entered from a PDF statement)
)ENGINE=innodb;
