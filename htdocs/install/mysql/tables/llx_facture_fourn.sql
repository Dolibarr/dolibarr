-- ===========================================================================
-- Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2007-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
-- ===========================================================================

create table llx_facture_fourn
(
  rowid					integer AUTO_INCREMENT PRIMARY KEY,
  ref					varchar(180) NOT NULL,
  ref_supplier			varchar(180) NOT NULL,
  entity				integer  DEFAULT 1 NOT NULL,	 -- multi company id

  ref_ext				varchar(255),                  -- reference into an external system (not used by dolibarr)

  type					smallint DEFAULT 0 NOT NULL,
  fk_soc				integer NOT NULL,
  
  datec					datetime,                      -- date de creation de la facture
  datef					date,                          -- date de la facture
  date_pointoftax		date DEFAULT NULL,			   -- date point of tax (for GB)
  date_valid			date,						   -- date validation
  tms					timestamp,                     -- date creation/modification
  libelle				varchar(255),
  paye					smallint         DEFAULT 0 NOT NULL,
  amount				double(24,8)     DEFAULT 0 NOT NULL,
  remise				double(24,8)     DEFAULT 0,

  close_code			varchar(16),		              -- Code motif cloture sans paiement complet
  close_note			varchar(128),		              -- Commentaire cloture sans paiement complet

  tva					double(24,8)     DEFAULT 0,
  localtax1				double(24,8)     DEFAULT 0,
  localtax2				double(24,8)     DEFAULT 0,
  total					double(24,8)     DEFAULT 0,
  total_ht				double(24,8)     DEFAULT 0,
  total_tva				double(24,8)     DEFAULT 0,
  total_ttc				double(24,8)     DEFAULT 0,

  fk_statut				smallint DEFAULT 0 NOT NULL,

  fk_user_author		integer,                       -- user making creation
  fk_user_modif         integer,                       -- user making last change
  fk_user_valid			integer,                       -- user validating

  fk_facture_source		integer,                       -- facture origine si facture avoir
  fk_projet				integer,                       -- projet auquel est associee la facture

  fk_account            integer,                       -- bank account
  fk_cond_reglement		integer,   	                   	-- condition de reglement (30 jours, fin de mois ...)
  fk_mode_reglement		integer,                	   	-- mode de reglement (CHQ, VIR, ...)
  date_lim_reglement 	date,                          	-- date limite de reglement

  note_private			text,
  note_public			text,
  fk_incoterms          integer,						-- for incoterms
  location_incoterms    varchar(255),					-- for incoterms
  model_pdf				varchar(255),
  last_main_doc			varchar(255),					-- relative filepath+filename of last main generated document

  import_key			varchar(14),
  extraparams			varchar(255),					-- for stock other parameters with json format
  
  fk_multicurrency		integer,
  multicurrency_code			varchar(255),
  multicurrency_tx			double(24,8) DEFAULT 1,
  multicurrency_total_ht		double(24,8) DEFAULT 0,
  multicurrency_total_tva	double(24,8) DEFAULT 0,
  multicurrency_total_ttc	double(24,8) DEFAULT 0
)ENGINE=innodb;
