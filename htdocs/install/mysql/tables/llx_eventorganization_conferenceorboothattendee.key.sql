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
-- along with this program.  If not, see https://www.gnu.org/licenses/.


-- BEGIN MODULEBUILDER INDEXES
ALTER TABLE llx_eventorganization_conferenceorboothattendee ADD INDEX idx_eventorganization_conferenceorboothattendee_rowid (rowid);
ALTER TABLE llx_eventorganization_conferenceorboothattendee ADD INDEX idx_eventorganization_conferenceorboothattendee_ref (ref);
ALTER TABLE llx_eventorganization_conferenceorboothattendee ADD INDEX idx_eventorganization_conferenceorboothattendee_fk_soc (fk_soc);
ALTER TABLE llx_eventorganization_conferenceorboothattendee ADD INDEX idx_eventorganization_conferenceorboothattendee_fk_actioncomm (fk_actioncomm);
ALTER TABLE llx_eventorganization_conferenceorboothattendee ADD INDEX idx_eventorganization_conferenceorboothattendee_fk_project (fk_project);
ALTER TABLE llx_eventorganization_conferenceorboothattendee ADD INDEX idx_eventorganization_conferenceorboothattendee_email (email);
ALTER TABLE llx_eventorganization_conferenceorboothattendee ADD INDEX idx_eventorganization_conferenceorboothattendee_status (status);
-- END MODULEBUILDER INDEXES

ALTER TABLE llx_eventorganization_conferenceorboothattendee ADD UNIQUE INDEX uk_eventorganization_conferenceorboothattendee(fk_project, email, fk_actioncomm);

