-- ===================================================================
-- Copyright (C) 2025 Florian HENRY  <florian.henry@scopen.fr>
-- Copyright (C) 2025 Laurent MAGNIN  <laurent.magnin@evarisk.com>
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
-- A record is an imported statement file (camt.053, MT940, OFX, CSV...) and/or the official statement issued by the bank (PDF),
-- the only legal proof, into which lines of llx_bank_import are reconciled. For an official statement, ref is llx_bank.num_releve.
-- @todo Rename table into llx_bank_statement

CREATE TABLE llx_bank_record
(
  rowid             integer     AUTO_INCREMENT PRIMARY KEY,
  ref 				varchar(50) NOT NULL,
  entity			integer		DEFAULT 1 NOT NULL,
  ref_ext			varchar(255) NULL,				-- reference of statement in an external system (open banking API...)
  fk_bank			integer		NOT NULL,
  dt_from			date		NOT NULL,
  dt_to				date		NOT NULL,
  opening_balance	double(24,8) NULL,				-- opening balance printed on the statement (camt.053 OPBD / MT940 :60F:)
  closing_balance	double(24,8) NULL,				-- closing balance printed on the statement (camt.053 CLBD / MT940 :62F:)
  total_debit		double(24,8) NULL,				-- total of debits (camt.053 TtlDbtNtries)
  total_credit		double(24,8) NULL,				-- total of credits (camt.053 TtlCdtNtries)
  nb_operations		integer		NULL,				-- number of lines (camt.053 TtlNtries)
  status			smallint	DEFAULT 0 NOT NULL,	-- 0=draft, 1=validated, 2=reconciled
  fk_user_author	integer		NULL,
  fk_user_modif		integer		NULL,
  fk_user_valid		integer		NULL,
  note_private		text		NULL,
  note_public		text		NULL,
  model_pdf			varchar(255) NULL,
  last_main_doc		varchar(255) NULL,
  import_key		varchar(14)	NULL,
  date_creation datetime NOT NULL,
  date_valid datetime NULL,
  tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)ENGINE=innodb;
