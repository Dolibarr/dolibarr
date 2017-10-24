-- Copyright (C) 2016	Laurent Destailleur	<eldy@users.sourceforge.net>
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
ALTER TABLE llx_websiteaccount ADD INDEX idx_websiteaccount_rowid (rowid);
ALTER TABLE llx_websiteaccount ADD INDEX idx_websiteaccount_login (login);
ALTER TABLE llx_websiteaccount ADD INDEX idx_websiteaccount_import_key (import_key);
ALTER TABLE llx_websiteaccount ADD INDEX idx_websiteaccount_status (status);
ALTER TABLE llx_websiteaccount ADD INDEX idx_websiteaccount_fk_soc (fk_soc);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_websiteaccount ADD CONSTRAINT llx_websiteaccount_field_id FOREIGN KEY (fk_field) REFERENCES llx_myotherobject(rowid);

