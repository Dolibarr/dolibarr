-- Copyright (C) ---Put here your own copyright and developer email---
-- Copyright (C) 2021  Gauthier VERDOL <gauthier.verdol@atm-consulting.fr>
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.


CREATE TABLE llx_stocktransfer_stocktransfer(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
    entity integer  DEFAULT 1 NOT NULL,
	ref varchar(128) DEFAULT '(PROV)' NOT NULL, 
	label varchar(255), 
	fk_soc integer, 
	fk_project integer,
    fk_warehouse_source integer,
    fk_warehouse_destination integer,
    description text,
	note_public text, 
	note_private text,
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    date_creation datetime NOT NULL,
    date_prevue_depart date DEFAULT NULL,
    date_reelle_depart date DEFAULT NULL,
    date_prevue_arrivee date DEFAULT NULL,
    date_reelle_arrivee date DEFAULT NULL,
    lead_time_for_warning integer DEFAULT NULL,
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer, 
	import_key varchar(14), 
	model_pdf varchar(255), 
	last_main_doc varchar(255),
	status smallint NOT NULL,
	fk_incoterms          integer, -- for incoterms
    location_incoterms    varchar(255)
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
