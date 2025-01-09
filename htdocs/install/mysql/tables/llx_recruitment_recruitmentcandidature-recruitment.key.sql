-- Copyright (C) 2020	Laurent Destailleur	<eldy@users.sourceforge.net>
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
ALTER TABLE llx_recruitment_recruitmentcandidature ADD INDEX idx_recruitment_recruitmentcandidature_rowid (rowid);
ALTER TABLE llx_recruitment_recruitmentcandidature ADD INDEX idx_recruitment_recruitmentcandidature_ref (ref);
ALTER TABLE llx_recruitment_recruitmentcandidature ADD CONSTRAINT llx_recruitment_recruitmentcandidature_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_recruitment_recruitmentcandidature ADD INDEX idx_recruitment_recruitmentcandidature_status (status);
-- END MODULEBUILDER INDEXES

ALTER TABLE llx_recruitment_recruitmentcandidature ADD UNIQUE INDEX uk_recruitmentcandidature_email_msgid(email_msgid);

-- ALTER TABLE llx_recruitment_recruitmentcandidature ADD CONSTRAINT llx_mymodule_myobject_fk_field FOREIGN KEY (fk_field) REFERENCES llx_mymodule_myotherobject(rowid);

