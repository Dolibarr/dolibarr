-- Copyright (C) 2023 Gauthier VERDOL <gauthier.verdol@atm-consulting.fr>
--
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


CREATE TABLE llx_element_time(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
    ref_ext varchar(32),                       -- reference into an external system (not used by dolibarr)
    fk_element integer NOT NULL,
    elementtype varchar(32) NOT NULL,
    element_date date,
    element_datehour datetime,
    element_date_withhour integer,
    element_duration double,
    fk_product integer,
    fk_user integer,
    thm double(24,8),
	invoice_id integer DEFAULT NULL,
	invoice_line_id integer DEFAULT NULL,
	intervention_id integer DEFAULT NULL,
	intervention_line_id integer DEFAULT NULL,
	import_key varchar(14),
	datec datetime,
    tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	note text
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
