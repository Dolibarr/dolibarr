-- ===================================================================
-- Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2010      Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
-- ===================================================================

create table llx_propal
(
  rowid					integer AUTO_INCREMENT PRIMARY KEY,
  ref					varchar(30) NOT NULL,			-- proposal reference number
  entity				integer DEFAULT 1 NOT NULL,		-- multi company id

  ref_ext				varchar(255),					-- reference into an external system (not used by dolibarr)
  ref_client			varchar(255),					-- customer proposal number

  fk_soc				integer,
  fk_projet				integer     DEFAULT NULL,		-- projet auquel est rattache la propale

  tms					timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  datec					datetime,						-- date de creation
  datep					date,							-- date de la propal
  fin_validite			datetime,						-- date de fin de validite
  date_valid			datetime,						-- date de validation
  date_signature		datetime,						-- date signature
  date_cloture			datetime,						-- date de cloture
  fk_user_author		integer,						-- user making creation
  fk_user_modif         integer,						-- user making last change
  fk_user_valid			integer,						-- user validating
  fk_user_signature		integer,						-- user signing (also means signed or not)
  fk_user_cloture		integer,						-- user closing
  fk_statut				smallint DEFAULT 0 NOT NULL,	-- 0=draft, 1=validated, 2=accepted, 3=refused, 4=billed/closed
  price					real         DEFAULT 0,			-- (obsolete)
  remise_percent		real         DEFAULT 0,			-- remise globale relative en pourcent (obsolete)
  remise_absolue		real         DEFAULT 0,			-- remise globale absolue (obsolete)
  remise				real         DEFAULT 0,			-- remise calculee (obsolete)
  total_ht				double(24,8) DEFAULT 0,			-- montant total ht apres remise globale
  total_tva             double(24,8) DEFAULT 0,			-- montant total tva apres remise globale
  localtax1				double(24,8) DEFAULT 0,			-- amount total localtax1
  localtax2				double(24,8) DEFAULT 0,			-- amount total localtax2
  total_ttc				double(24,8) DEFAULT 0,			-- montant total ttc apres remise globale

  fk_account			integer,						-- bank account
  fk_currency			varchar(3),						-- currency code
  fk_cond_reglement		integer,						-- condition de reglement (30 jours, fin de mois ...)
  deposit_percent		varchar(63) DEFAULT NULL,		-- default deposit % if payment term needs it
  fk_mode_reglement		integer,						-- mode de reglement (Virement, Prelevement)

  online_sign_ip		varchar(48),
  online_sign_name		varchar(64),

  note_private			text,
  note_public			text,

  model_pdf				varchar(255),					-- last template used to generate main document (exemple: azur, generic_invoice_odt:/pathto/template_invoice.odt)
  model_pdf_pos_sign 	varchar(32),					-- last position used to include the signature (example: posX:posY:Height:Width)
  last_main_doc			varchar(255),					-- relative filepath+filename of the last main generated document

  date_livraison		date DEFAULT NULL,				-- delivery date
  fk_shipping_method    integer,                        -- shipping method id
  fk_warehouse		    integer DEFAULT NULL,           -- warehouse id
  fk_availability		integer NULL,
  fk_input_reason		integer,
  fk_incoterms          integer,										-- for incoterms
  location_incoterms    varchar(255),								-- for incoterms
  import_key			varchar(14),
  extraparams			varchar(255),					-- for stock other parameters with json format
  fk_delivery_address	integer,							-- delivery address (deprecated)

  fk_multicurrency			integer,
  multicurrency_code		varchar(3),
  multicurrency_tx			double(24,8) DEFAULT 1,
  multicurrency_total_ht	double(24,8) DEFAULT 0,
  multicurrency_total_tva	double(24,8) DEFAULT 0,
  multicurrency_total_ttc	double(24,8) DEFAULT 0
)ENGINE=innodb;
