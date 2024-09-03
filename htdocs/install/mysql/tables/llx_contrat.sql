-- ============================================================================
-- Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2005-2014 Regis Houssin        <regis.houssin@inodbox.com>
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

create table llx_contrat
(
  rowid						integer AUTO_INCREMENT PRIMARY KEY,
  ref						varchar(255),		            -- contrat reference
  ref_customer				varchar(255),		            -- customer contract ref
  ref_supplier				varchar(255),		            -- supplier contract ref
  ref_ext					varchar(255),		            -- external contract ref
  entity					integer DEFAULT 1 NOT NULL,		-- multi company id
  tms						timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  datec						datetime,                   	-- creation date
  date_contrat				datetime,
  statut					smallint DEFAULT 0,				-- not used. deprecated

  fin_validite				datetime,	-- not used
  date_cloture				datetime,	-- not used

  fk_soc					integer NOT NULL,
  fk_projet					integer,
  fk_commercial_signature	integer, 	-- obsolete
  fk_commercial_suivi 		integer, 	-- obsolete
  fk_user_author			integer NOT NULL default 0,
  fk_user_modif				integer,
  fk_user_cloture			integer,

  total_tva                 double(24,8)     DEFAULT 0,		-- total tax
  localtax1					double(24,8)     DEFAULT 0,		-- total local tax 2
  localtax2					double(24,8)     DEFAULT 0,		-- total local tax 3
  revenuestamp              double(24,8)     DEFAULT 0,		-- amount total revenuestamp (usefull for proforma that must match invoice)
  total_ht					double(24,8)     DEFAULT 0,		-- total without tax
  total_ttc					double(24,8)     DEFAULT 0,		-- total with tax

  denormalized_lower_planned_end_date datetime,

  signed_status        		smallint DEFAULT NULL,          -- signed status NULL, 0 or 1
  online_sign_ip			varchar(48),
  online_sign_name			varchar(64),

  note_private				text,
  note_public				text,

  model_pdf					varchar(255),
  last_main_doc			    varchar(255),					-- relative filepath+filename of last main generated document

  import_key				varchar(14),
  extraparams				varchar(255)
)ENGINE=innodb;
