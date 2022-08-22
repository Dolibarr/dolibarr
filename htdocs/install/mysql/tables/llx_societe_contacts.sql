-- ========================================================================
-- Copyright (C) 2019 Florian HENRY <florian.henry@atm-consulting.fr>
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
-- ========================================================================

-- This table contains the contacts by default of a thirdparty
-- Such contacts will be added to document automatiall if their role match the one expected by the document.

create table llx_societe_contacts
(
    rowid           integer AUTO_INCREMENT PRIMARY KEY,
    entity          integer DEFAULT 1 NOT NULL,
    date_creation           datetime NOT NULL,
    fk_soc		        integer NOT NULL,
    fk_c_type_contact	int NOT NULL,
    fk_socpeople        integer NOT NULL,
    tms TIMESTAMP,
    import_key VARCHAR(14)
)ENGINE=innodb;
