-- ===================================================================
-- Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
-- Copyright (C) 2009-2014	Laurent Destailleur		<eldy@users.sourceforge.net>
-- Copyright (C) 2010		Juanjo Menent			<jmenent@2byte.es>
-- Copyright (C) 2010-2012	Regis Houssin			<regis.houssin@inodbox.com>
-- Copyright (C) 2012		Cédric Salvador			<csalvador@gpcsolutions.fr>
-- Copyright (C) 2016-2018	Charlene Benke			<charlie@patas-monkey.com>
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

create table llx_fichinterdet_rec
(
	rowid				integer AUTO_INCREMENT PRIMARY KEY,
	fk_fichinter		integer NOT NULL,
	date				datetime,				-- date de la ligne d'intervention
	description			text,					-- description de la ligne d'intervention
	duree				integer,				-- duree de la ligne d'intervention
	rang				integer DEFAULT 0,		-- ordre affichage sur la fiche
	total_ht			DOUBLE(24, 8) NULL DEFAULT NULL,
	subprice			DOUBLE(24, 8) NULL DEFAULT NULL,
	fk_parent_line		integer NULL DEFAULT NULL,
	fk_product			integer NULL DEFAULT NULL,
	label				varchar(255) NULL DEFAULT NULL,
	tva_tx				DOUBLE(6, 3) NULL DEFAULT NULL,
	localtax1_tx		DOUBLE(6, 3) NULL DEFAULT 0,
	localtax1_type		VARCHAR(1) NULL DEFAULT NULL,
	localtax2_tx		DOUBLE(6, 3) NULL DEFAULT 0,
	localtax2_type		VARCHAR(1) NULL DEFAULT NULL,
	qty					double NULL DEFAULT NULL,
	remise_percent		double NULL DEFAULT 0,
	remise				double NULL DEFAULT 0,
	fk_remise_except	integer NULL DEFAULT NULL,
	price				DOUBLE(24, 8) NULL DEFAULT NULL,
	total_tva			DOUBLE(24, 8) NULL DEFAULT NULL,
	total_localtax1		DOUBLE(24, 8) NULL DEFAULT 0,
	total_localtax2		DOUBLE(24, 8) NULL DEFAULT 0,
	total_ttc			DOUBLE(24, 8) NULL DEFAULT NULL,
	product_type		INTEGER NULL DEFAULT 0,
	date_start			datetime NULL DEFAULT NULL,
	date_end			datetime NULL DEFAULT NULL,
	info_bits			INTEGER NULL DEFAULT 0,
	buy_price_ht		DOUBLE(24, 8) NULL DEFAULT 0,
	fk_product_fournisseur_price	integer NULL DEFAULT NULL,
	fk_code_ventilation	integer NOT NULL DEFAULT 0,
	fk_export_commpta	integer NOT NULL DEFAULT 0,
	special_code		integer UNSIGNED NULL DEFAULT 0,
	fk_unit				integer NULL DEFAULT NULL,	
	import_key			varchar(14) NULL DEFAULT NULL

)ENGINE=innodb;
