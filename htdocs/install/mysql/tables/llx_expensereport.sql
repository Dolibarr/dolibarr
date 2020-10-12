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
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
-- ============================================================================

CREATE TABLE llx_expensereport (
  rowid integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ref        		varchar(50) NOT NULL,
  entity 			integer DEFAULT 1 NOT NULL,		-- multi company id
  ref_number_int 	integer DEFAULT NULL,
  ref_ext 			integer,
  total_ht 			double(24,8) DEFAULT 0,
  total_tva 		double(24,8) DEFAULT 0,
  localtax1			double(24,8) DEFAULT 0,			-- amount total localtax1
  localtax2			double(24,8) DEFAULT 0,			-- amount total localtax2
  total_ttc 		double(24,8) DEFAULT 0,
  date_debut 		date NOT NULL,
  date_fin 			date NOT NULL,
  date_create 		datetime NOT NULL,
  date_valid 		datetime,
  date_approve		datetime,
  date_refuse 		datetime,
  date_cancel 		datetime,
  tms 		 		timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_author 	integer NOT NULL,				-- not the user author but the user the expense report is for
  fk_user_creat 	integer DEFAULT NULL,			-- the use author
  fk_user_modif 	integer DEFAULT NULL,
  fk_user_valid 	integer DEFAULT NULL,
  fk_user_validator integer DEFAULT NULL,
  fk_user_approve   integer DEFAULT NULL,
  fk_user_refuse 	integer DEFAULT NULL,
  fk_user_cancel 	integer DEFAULT NULL,
  fk_statut			integer NOT NULL,				-- 1=brouillon, 2=validated (waiting approval), 4=canceled, 5=approved, 6=payed, 99=refused
  fk_c_paiement 	integer DEFAULT NULL,			-- deprecated
  paid              smallint default 0 NOT NULL,	-- deprecated (status is used instead)
  note_public		text,
  note_private 		text,
  detail_refuse 	varchar(255) DEFAULT NULL,
  detail_cancel 	varchar(255) DEFAULT NULL,
  integration_compta integer DEFAULT NULL,			-- not used
  fk_bank_account 	integer DEFAULT NULL,
  model_pdf 		varchar(50) DEFAULT NULL,
  
  fk_multicurrency        integer,
  multicurrency_code      varchar(255),
  multicurrency_tx        double(24,8) DEFAULT 1,
  multicurrency_total_ht  double(24,8) DEFAULT 0,
  multicurrency_total_tva double(24,8) DEFAULT 0,
  multicurrency_total_ttc double(24,8) DEFAULT 0,

  import_key			varchar(14),
  extraparams			varchar(255)				-- for other parameters with json format
) ENGINE=innodb;

