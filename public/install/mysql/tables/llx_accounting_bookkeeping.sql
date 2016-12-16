-- ============================================================================
-- Copyright (C) 2013-2014 Olivier Geffroy      <jeff@jeffinfo.com>
-- Copyright (C) 2013-2014 Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
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

CREATE TABLE llx_accounting_bookkeeping 
(
  rowid				integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
  entity			integer DEFAULT 1 NOT NULL,	  -- multi company id
  doc_date			date NOT NULL,
  doc_type			varchar(30) NOT NULL,	-- facture_client/reglement_client/facture_fournisseur/reglement_fournisseur
  doc_ref			varchar(300) NOT NULL,	-- facture_client/reglement_client/... reference number
  fk_doc			integer NOT NULL,		-- facture_client/reglement_client/... rowid
  fk_docdet			integer NOT NULL,		-- facture_client/reglement_client/... line rowid
  code_tiers		varchar(24),			-- code tiers
  numero_compte		varchar(32) NOT NULL,
  label_compte		varchar(128) NOT NULL,
  debit				double NOT NULL,
  credit			double NOT NULL,
  montant			double NOT NULL,
  sens				varchar(1) DEFAULT NULL,
  fk_user_author	integer NOT NULL,						-- user creating
  fk_user_modif     integer,                                -- user making last change
  date_creation		datetime,								-- date de creation
  tms               timestamp,								-- date last modification 
  import_key		varchar(14),
  code_journal		varchar(10) NOT NULL,
  piece_num			integer NOT NULL,
  validated         tinyint DEFAULT 0 NOT NULL -- 0 line not validated / 1 line validated (No deleting / No modification) 
) ENGINE=innodb;
