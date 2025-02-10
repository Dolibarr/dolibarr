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
-- along with this program.  If not, see https://www.gnu.org/licenses/.


-- BEGIN MODULEBUILDER INDEXES
ALTER TABLE llx_societe_account ADD INDEX idx_societe_account_rowid (rowid);
ALTER TABLE llx_societe_account ADD INDEX idx_societe_account_login (login);
ALTER TABLE llx_societe_account ADD INDEX idx_societe_account_status (status);
ALTER TABLE llx_societe_account ADD INDEX idx_societe_account_fk_website (fk_website);
ALTER TABLE llx_societe_account ADD INDEX idx_societe_account_fk_soc (fk_soc);
-- END MODULEBUILDER INDEXES

-- Only one unique login in the same website (note: we can still have the same login in database for 2 different companies if fk_website is null)
ALTER TABLE llx_societe_account ADD UNIQUE INDEX uk_societe_account_login_website(entity, login, site, fk_website);
-- Only one unique login in the same external account in the same company
ALTER TABLE llx_societe_account ADD UNIQUE INDEX uk_societe_account_key_account_soc(entity, fk_soc, key_account, site, fk_website);


-- Table website does not always exists
--ALTER TABLE llx_societe_account ADD CONSTRAINT llx_societe_account_fk_website FOREIGN KEY (fk_website) REFERENCES llx_website(rowid);

ALTER TABLE llx_societe_account ADD CONSTRAINT llx_societe_account_fk_societe FOREIGN KEY (fk_soc) REFERENCES llx_societe(rowid);
