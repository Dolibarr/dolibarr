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


-- BEGIN MODULEBUILDER INDEXES
ALTER TABLE llx_emailcollector_emailcollector ADD INDEX idx_emailcollector_entity (entity);
ALTER TABLE llx_emailcollector_emailcollector ADD INDEX idx_emailcollector_status (status);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_emailcollector_emailcollector ADD UNIQUE INDEX uk_emailcollector_emailcollector_fieldxyz(fieldx, fieldy);

--ALTER TABLE llx_emailcollector_emailcollector ADD CONSTRAINT llx_emailcollector_emailcollector_field_id FOREIGN KEY (fk_field) REFERENCES llx_myotherobject(rowid);

