-- ============================================================================
-- Copyright (C) 2015		Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- ============================================================================

CREATE TABLE llx_expensereport_det
(
   rowid integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
   fk_expensereport integer NOT NULL,
   docnumber varchar(128),										-- To store a ref of a accounting doc (piece)
   fk_c_type_fees integer NOT NULL,								-- Type of expense
   fk_c_exp_tax_cat integer,
   fk_projet integer,											-- Id of project
   comments text NOT NULL,
   product_type integer DEFAULT -1,
   qty real NOT NULL,
   subprice						double(24,8) DEFAULT 0 NOT NULL, -- P.U. HT (example 100)
   value_unit                   double(24,8) NOT NULL,          -- P.U. TTC (example 120)
   remise_percent real,
   vat_src_code					varchar(10)  DEFAULT '',		-- Vat code used as source of vat fields. Not strict foreign key here.
   tva_tx						double(6,3),					-- Vat rate
   localtax1_tx               	double(6,3)  DEFAULT 0,    		-- localtax1 rate
   localtax1_type			 	varchar(10)	  	 NULL, 			-- localtax1 type
   localtax2_tx               	double(6,3)  DEFAULT 0,    		-- localtax2 rate
   localtax2_type			 	varchar(10)	  	 NULL, 			-- localtax2 type
   total_ht double(24,8) DEFAULT 0 NOT NULL,
   total_tva double(24,8) DEFAULT 0 NOT NULL,
   total_localtax1				double(24,8)  	DEFAULT 0,		-- Total LocalTax1 for total quantity of line
   total_localtax2				double(24,8)	DEFAULT 0,		-- total LocalTax2 for total quantity of line
   total_ttc double(24,8) DEFAULT 0 NOT NULL,
   date date NOT NULL,
   info_bits					integer DEFAULT 0,				-- TVA NPR ou non
   special_code					integer DEFAULT 0,			    -- code pour les lignes speciales
   fk_multicurrency             integer,
   multicurrency_code           varchar(255),
   multicurrency_subprice       double(24,8) DEFAULT 0,
   multicurrency_total_ht       double(24,8) DEFAULT 0,
   multicurrency_total_tva      double(24,8) DEFAULT 0,
   multicurrency_total_ttc      double(24,8) DEFAULT 0,
   fk_facture					integer DEFAULT 0,				-- ID of customer invoice line if expense is rebilled to a customer
   fk_expensereport_line        integer DEFAULT NULL,			-- ID of ECM file that is source document of expense report 
   fk_code_ventilation			integer DEFAULT 0,
   rang							integer DEFAULT 0,				-- position of line
   import_key					varchar(14),
   rule_warning_message text
) ENGINE=innodb;