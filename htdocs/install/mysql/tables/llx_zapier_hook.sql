-- Copyright (C) ---Put here your own copyright and developer email---
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
-- along with this program.  If not, see http://www.gnu.org/licenses/.


CREATE TABLE llx_zapier_hook(
    rowid integer AUTO_INCREMENT PRIMARY KEY,
    entity integer DEFAULT 1 NOT NULL,
    url varchar(255),
    event varchar(255),
    module varchar(128),
    action varchar(128),
    status integer,
    date_creation DATETIME NOT NULL,
    fk_user integer NOT NULL,
    tms TIMESTAMP NOT NULL,
    import_key varchar(14)
) ENGINE=innodb;
