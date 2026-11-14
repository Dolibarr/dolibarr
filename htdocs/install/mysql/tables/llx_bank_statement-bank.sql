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

CREATE TABLE llx_bank_statement
(
  rowid             integer     AUTO_INCREMENT PRIMARY KEY,
  ref 				varchar(50) NOT NULL,
  entity			integer		DEFAULT 1 NOT NULL,
  ref_ext			varchar(255) NULL,
  fk_bank			integer		NOT NULL,
  dt_from			date		NOT NULL,
  dt_to				date		NOT NULL,
  opening_balance	double(24,8) NULL DEFAULT NULL,
  closing_balance	double(24,8) NULL DEFAULT NULL,
  total_debit		double(24,8) NULL DEFAULT NULL,
  total_credit		double(24,8) NULL DEFAULT NULL,
  nb_operations		integer		NULL DEFAULT NULL,
  status			smallint	DEFAULT 0 NOT NULL,
  fk_user_author	integer		NULL DEFAULT NULL,
  fk_user_modif		integer		NULL DEFAULT NULL,
  fk_user_valid		integer		NULL DEFAULT NULL,
  note_private		text		NULL DEFAULT NULL,
  note_public		text		NULL DEFAULT NULL,
  model_pdf			varchar(255) NULL DEFAULT NULL,
  last_main_doc		varchar(255) NULL DEFAULT NULL,
  date_creation datetime NOT NULL,
  date_valid datetime NULL,
  tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  import_key		varchar(14) NULL DEFAULT NULL
)ENGINE=innodb;
