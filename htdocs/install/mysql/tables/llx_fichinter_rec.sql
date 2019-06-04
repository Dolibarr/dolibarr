-- ===========================================================================
-- Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2012-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2009      Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
-- Copyright (C) 2018      Charlene Benke		    <charlie@patas-monkey.com>
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

create table llx_fichinter_rec
(
	rowid				integer AUTO_INCREMENT PRIMARY KEY,
	titre				varchar(50) NOT NULL,
	entity				integer DEFAULT 1 NOT NULL,	 -- multi company id
	fk_soc				integer DEFAULT NULL,
	datec				datetime,  -- date de creation
	
	fk_contrat			integer DEFAULT 0,          -- contrat auquel est rattache la fiche
	fk_user_author		integer,             -- createur
	fk_projet			integer,             -- projet auquel est associe la facture
	duree				real,                       -- duree totale de l'intervention
	description			text,
	modelpdf			varchar(50),
	note_private		text,
	note_public			text,

	frequency			integer,					-- frequency (for example: 3 for every 3 month)
	unit_frequency		varchar(2) DEFAULT 'm',		-- 'm' for month (date_when must be a day <= 28), 'y' for year, ... 
	date_when			datetime DEFAULT NULL,		-- date for next gen (when an invoice is generated, this field must be updated with next date)
	date_last_gen		datetime DEFAULT NULL,		-- date for last gen (date with last successfull generation of invoice)
	nb_gen_done			integer DEFAULT NULL,		-- nb of generation done (when an invoice is generated, this field must incremented)
	nb_gen_max			integer DEFAULT NULL,		-- maximum number of generation
	auto_validate		integer NULL DEFAULT NULL	-- statut of the generated intervention

)ENGINE=innodb;
