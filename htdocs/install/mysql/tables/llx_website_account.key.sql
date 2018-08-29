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
ALTER TABLE llx_website_account ADD INDEX idx_website_account_rowid (rowid);
ALTER TABLE llx_website_account ADD INDEX idx_website_account_login (login);
ALTER TABLE llx_website_account ADD INDEX idx_website_account_import_key (import_key);
ALTER TABLE llx_website_account ADD INDEX idx_website_account_status (status);
ALTER TABLE llx_website_account ADD INDEX idx_website_account_fk_soc (fk_soc);
ALTER TABLE llx_website_account ADD INDEX idx_website_account_fk_website (fk_website);
-- END MODULEBUILDER INDEXES

ALTER TABLE llx_website_account ADD UNIQUE INDEX uk_website_account_login_website_soc(login, fk_website, fk_soc);

ALTER TABLE llx_website_account ADD CONSTRAINT llx_website_account_fk_website FOREIGN KEY (fk_website) REFERENCES llx_website(rowid);

