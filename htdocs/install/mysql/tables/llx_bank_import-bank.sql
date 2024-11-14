-- ===================================================================
-- Copyright (C) 2024 Laurent Destailleur  <eldy@users.sourceforge.net>
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

CREATE TABLE llx_bank_import
(
  rowid                 integer         AUTO_INCREMENT PRIMARY KEY,
  id_account			integer			NOT NULL,              	-- bank account ID in Dolibarr
  record_type 			varchar(64)   	NULL,                  	-- OFX Type of transaction: DIRECTDEBIT, XFER, OTHER or code/type of operation
  label         		varchar(255)  	NOT NULL,               -- label of operation
  record_type_origin  	varchar(255)  	NOT NULL,               -- operation code/type origin
  label_origin  		varchar(255)  	NOT NULL,               -- label of operation origin
  comment				text			NULL,                   -- Comment/Motif
  note				    text			NULL,                   -- Notes like "References"
  bdate					date			NULL,                   -- date operation
  vdate					date			NULL,                   -- date value
  date_scraped			datetime		NULL,                  	-- date discarded
  original_amount		double(24,8)	NULL,                	-- OFX amount
  original_currency		varchar(255)	NULL,              		-- OFX Currency
  amount_debit			double(24,8)	NOT NULL,          		-- money spent. For statement using debit/credit. For statement using 1 amount, use original_amount.
  amount_credit       	double(24,8)  NOT NULL,          		-- money received. For statement using debit/credit. For statement using 1 amount, use original_amount.
  deleted_date			datetime		NULL,                  	-- to flag this record as deleted
  fk_duplicate_of		integer			NULL,                  	-- to flag this record as a duplicate of another one
  status				smallint		NOT NULL,               -- 0=just imported
  datec					datetime		NOT NULL,		        -- date creation
  tms					timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,	-- date of last modification
  fk_user_author	    integer         NOT NULL, 		   		-- user who created the record
  fk_user_modif		    integer,					            -- user who modified the record
  import_key			varchar(14),					        -- import key
  datas					text			NOT NULL                -- full record/line coming from source
)ENGINE=innodb;
