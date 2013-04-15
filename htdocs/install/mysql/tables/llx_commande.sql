-- ===================================================================
-- Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
-- ===================================================================

create table llx_commande
(
  rowid					integer AUTO_INCREMENT PRIMARY KEY,
  ref					varchar(30)       NOT NULL,		-- order reference number
  entity				integer DEFAULT 1 NOT NULL,		-- multi company id

  ref_ext				varchar(255),					-- reference into an external system (not used by dolibarr)
  ref_int				varchar(255),					-- reference into an internal system (used by dolibarr)
  ref_client			varchar(255),					-- reference for customer

  fk_soc				integer NOT NULL,
  fk_projet				integer DEFAULT NULL,			-- projet auquel est rattache la commande

  tms					timestamp,
  date_creation			datetime,						-- date de creation 
  date_valid			datetime,						-- date de validation
  date_cloture			datetime,						-- date de cloture
  date_commande			date,							-- date de la commande
  fk_user_author		integer,						-- createur de la commande
  fk_user_valid			integer,						-- valideur de la commande
  fk_user_cloture		integer,						-- auteur cloture
  source				smallint,
  fk_statut				smallint  default 0,
  amount_ht				real      default 0,
  remise_percent		real      default 0,
  remise_absolue		real      default 0,
  remise				real      default 0,
  tva					double(24,8)      default 0,
  localtax1				double(24,8)      default 0,	-- total localtax1 
  localtax2				double(24,8)      default 0,	-- total localtax2
  total_ht				double(24,8)      default 0,
  total_ttc				double(24,8)      default 0,
  note_private			text,
  note_public			text,
  model_pdf				varchar(255),

  facture				tinyint   default 0,
  fk_account			integer,						-- bank account
  fk_currency			varchar(2),						-- currency code
  fk_cond_reglement		integer,						-- condition de reglement
  fk_mode_reglement		integer,						-- mode de reglement
  
  date_livraison		date 	  default NULL,
  fk_availability		integer NULL,
  fk_input_reason		integer,
  fk_delivery_address	integer,						-- delivery address (deprecated)
  import_key			varchar(14),
  extraparams			varchar(255)					-- for stock other parameters with json format
  
)ENGINE=innodb;
