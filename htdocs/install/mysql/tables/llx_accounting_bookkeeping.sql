-- ============================================================================
-- Copyright (C) 2013-2014 Olivier Geffroy      <jeff@jeffinfo.com>
-- Copyright (C) 2013-2019 Alexandre Spangaro   <aspangaro@open-dsi.fr>
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
-- ============================================================================

CREATE TABLE llx_accounting_bookkeeping
(
  rowid                 integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
  entity                integer DEFAULT 1 NOT NULL,	-- 					| multi company id
  piece_num             integer NOT NULL,			-- FEC:EcritureNum  | accounting transaction id
  doc_date              date NOT NULL,				-- FEC:PieceDate    | date of source document
  doc_type              varchar(30) NOT NULL,		-- 					| facture_client/reglement_client/facture_fournisseur/reglement_fournisseur/import
  doc_ref               varchar(300) NOT NULL,		-- FEC:PieceRef		| facture_client/reglement_client/... reference number
  fk_doc                integer NOT NULL,			-- 					| facture_client/reglement_client/... rowid
  fk_docdet             integer NOT NULL,			-- 					| facture_client/reglement_client/... line rowid
  thirdparty_code       varchar(32),                -- Third party code (customer or supplier) when record is saved (may help debug)
  subledger_account     varchar(32),				-- FEC:CompAuxNum	| account number of subledger account
  subledger_label       varchar(255),				-- FEC:CompAuxLib	| label of subledger account
  numero_compte         varchar(32) NOT NULL,		-- FEC:CompteNum	| account number
  label_compte          varchar(255) NOT NULL,		-- FEC:CompteLib	| label of account
  label_operation       varchar(255),				-- FEC:EcritureLib	| label of the operation
  debit                 double(24,8) NOT NULL,		-- FEC:Debit
  credit                double(24,8) NOT NULL,		-- FEC:Credit
  montant               double(24,8) NOT NULL,		-- FEC:Montant (Not necessary)
  sens                  varchar(1) DEFAULT NULL,	-- FEC:Sens (Not necessary)
  multicurrency_amount  double(24,8),				-- FEC:Montantdevise
  multicurrency_code    varchar(255),				-- FEC:Idevise
  lettering_code        varchar(255),				-- FEC:EcritureLet
  date_lettering        datetime,					-- FEC:DateLet
  date_lim_reglement    datetime DEFAULT NULL,		-- 					| date limite de reglement
  fk_user_author        integer NOT NULL,			-- 					| user creating
  fk_user_modif         integer,					-- 					| user making last change
  date_creation         datetime,					-- FEC:EcritureDate	| creation date
  tms                   timestamp,					--					| date last modification
  fk_user               integer NULL,               -- The id of user that validate the accounting source document
  code_journal          varchar(32) NOT NULL,		-- FEC:JournalCode
  journal_label         varchar(255),				-- FEC:JournalLib
  date_validated        datetime,					-- FEC:ValidDate	| if empty: movement not validated / if not empty: movement validated (No deleting / No modification)
  date_export	      	datetime DEFAULT NULL,		--
  import_key            varchar(14),
  extraparams           varchar(255)				-- for other parameters with json format
) ENGINE=innodb;
