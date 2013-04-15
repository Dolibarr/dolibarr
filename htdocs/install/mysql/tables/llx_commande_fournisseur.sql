-- ===================================================================
-- Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
-- Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
-- Copyright (C) 2007		Laurent Destailleur		<eldy@users.sourceforge.net>
-- Copyright (C) 2010		Juanjo Menent			<jmenent@2byte.es>
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

create table llx_commande_fournisseur
(
  rowid					integer AUTO_INCREMENT PRIMARY KEY,

  ref					varchar(30) NOT NULL,          -- order number
  entity				integer DEFAULT 1 NOT NULL,	 -- multi company id

  ref_ext				varchar(30),                  -- reference into an external system (not used by dolibarr)
  ref_supplier			varchar(30),

  fk_soc				integer NOT NULL,
  fk_projet				integer DEFAULT 0,             -- projet auquel est rattache la commande

  tms					timestamp,
  date_creation			datetime,                      -- date de creation 
  date_valid			datetime,                      -- date de validation
  date_approve			datetime,                      -- date de approve
  date_commande			date,                          -- date de la commande
  fk_user_author		integer,                       -- createur de la commande
  fk_user_valid			integer,                       -- valideur de la commande
  fk_user_approve		integer,                       -- auteur approve
  source				smallint NOT NULL,
  fk_statut				smallint  default 0,
  amount_ht				real      default 0,
  remise_percent		real      default 0,
  remise				real      default 0,
  tva					double(24,8)      default 0,
  localtax1				double(24,8)      default 0,
  localtax2				double(24,8)      default 0,
  total_ht				double(24,8)      default 0,
  total_ttc				double(24,8)      default 0,
  note_private			text,
  note_public			text,
  model_pdf				varchar(255),

  date_livraison		date 	  default NULL,
  fk_cond_reglement		integer,                       -- condition de reglement
  fk_mode_reglement		integer,                       -- mode de reglement
  fk_input_method	integer default 0,
  import_key			varchar(14),
  extraparams			varchar(255)					-- for stock other parameters with json format
  
)ENGINE=innodb;
