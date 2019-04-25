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


-- BEGIN MODULEBUILDER INDEXES
ALTER TABLE llx_emailcollector_emailcollectorfilter ADD INDEX idx_emailcollector_fk_emailcollector (fk_emailcollector);
ALTER TABLE llx_emailcollector_emailcollectorfilter ADD CONSTRAINT fk_emailcollectorfilter_fk_emailcollector FOREIGN KEY (fk_emailcollector) REFERENCES llx_emailcollector_emailcollector(rowid);
-- END MODULEBUILDER INDEXES

ALTER TABLE llx_emailcollector_emailcollectorfilter ADD UNIQUE INDEX uk_emailcollector_emailcollectorfilter (fk_emailcollector, type, rulevalue);

