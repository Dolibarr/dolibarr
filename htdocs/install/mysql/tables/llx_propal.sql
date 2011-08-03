-- ===================================================================
-- Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
-- Copyright (C) 2010      Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
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
-- $Id: llx_propal.sql,v 1.14 2011/08/03 01:25:38 eldy Exp $
-- ===================================================================

create table llx_propal
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  ref             varchar(30) NOT NULL,			-- proposal reference number
  entity          integer DEFAULT 1 NOT NULL,   -- multi company id

  ref_ext         varchar(50),                  -- reference into an external system (not used by dolibarr)
  ref_int		  varchar(50),                  -- reference into an internal system (used by dolibarr)
  ref_client      varchar(50),					-- customer proposal number

  fk_soc          integer,
  fk_projet       integer     DEFAULT 0,        -- projet auquel est rattache la propale

  tms             timestamp,
  datec           datetime,						-- date de creation 
  datep           date,							-- date de la propal
  fin_validite    datetime,						-- date de fin de validite
  date_valid      datetime,						-- date de validation
  date_cloture    datetime,						-- date de cloture
  fk_user_author  integer,						-- createur de la propale
  fk_user_valid   integer,						-- valideur de la propale
  fk_user_cloture integer,						-- cloture de la propale signee ou non signee
  fk_statut       smallint DEFAULT 0 NOT NULL,
  price           real         DEFAULT 0,		-- (obsolete)
  remise_percent  real         DEFAULT 0,		-- remise globale relative en pourcent (obsolete)
  remise_absolue  real         DEFAULT 0,		-- remise globale absolue (obsolete)
  remise          real         DEFAULT 0,		-- remise calculee (obsolete)
  total_ht        double(24,8) DEFAULT 0,		-- montant total ht apres remise globale
  tva             double(24,8) DEFAULT 0,		-- montant total tva apres remise globale
  localtax1       double(24,8) DEFAULT 0,		-- amount total localtax1
  localtax2		  double(24,8) DEFAULT 0,		-- amount total localtax2
  total           double(24,8) DEFAULT 0,		-- montant total ttc apres remise globale

  fk_cond_reglement		integer,				-- condition de reglement (30 jours, fin de mois ...)
  fk_mode_reglement		integer,				-- mode de reglement (Virement, Prelevement)
 
  note					text,
  note_public			text,
  model_pdf				varchar(255),
  date_livraison 		date DEFAULT NULL,		-- delivery date
  fk_availability 		integer NULL,
  fk_demand_reason		integer,
  fk_adresse_livraison  integer					-- delivery address (deprecated)
)ENGINE=innodb;
