-- Copyright (C) 2018 Laurent Destailleur	<eldy@users.sourceforge.net>
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


CREATE TABLE llx_emailcollector_emailcollector(
        -- BEGIN MODULEBUILDER FIELDS
        rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
        entity integer DEFAULT 1 NOT NULL, 
        ref varchar(128) NOT NULL,
        label varchar(255), 
        description text,
        host varchar(255), 
        login varchar(128), 
        password varchar(128),
        source_directory varchar(255) NOT NULL,
        target_directory varchar(255),
        datelastresult datetime, 
        codelastresult varchar(16), 
        lastresult varchar(255),
        note_public text, 
        note_private text, 
        date_creation datetime NOT NULL, 
        tms timestamp NOT NULL, 
        fk_user_creat integer NOT NULL, 
        fk_user_modif integer, 
        import_key varchar(14), 
        status integer NOT NULL
        -- END MODULEBUILDER FIELDS
) ENGINE=innodb;
