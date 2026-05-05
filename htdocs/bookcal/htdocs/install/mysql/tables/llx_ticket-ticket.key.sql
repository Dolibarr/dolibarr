-- SQL definition for module ticket
-- Copyright (C) 2013  Jean-François FERRY <hello@librethic.io>
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
-- along with this program.  If not, see <https://www.gnu.org/licenses/>.

ALTER TABLE llx_ticket ADD UNIQUE uk_ticket_track_id (track_id);
ALTER TABLE llx_ticket ADD UNIQUE uk_ticket_ref (ref, entity);
ALTER TABLE llx_ticket ADD INDEX idx_ticket_entity (entity);
ALTER TABLE llx_ticket ADD INDEX idx_ticket_fk_soc (fk_soc);
ALTER TABLE llx_ticket ADD INDEX idx_ticket_fk_user_assign (fk_user_assign);
ALTER TABLE llx_ticket ADD INDEX idx_ticket_fk_project (fk_project);
ALTER TABLE llx_ticket ADD INDEX idx_ticket_fk_statut (fk_statut);

ALTER TABLE llx_ticket ADD UNIQUE INDEX uk_ticket_barcode_barcode_type (barcode, fk_barcode_type, entity);
ALTER TABLE llx_ticket ADD CONSTRAINT llx_ticket_fk_product_barcode_type FOREIGN KEY (fk_barcode_type) REFERENCES  llx_c_barcode_type (rowid);

-- Idea for better perf to get last num of ticket on large databases
-- ALTER TABLE llx_ticket ADD COLUMN calculated_numrefonly INTEGER AS (CASE SUBSTRING(ref FROM 1 FOR 2) WHEN 'TS' THEN CAST(SUBSTRING(ref FROM 8) AS SIGNED) ELSE 0 END) PERSISTENT;
-- ALTER TABLE llx_ticket ADD INDEX idx_calculated_numrefonly (calculated_numrefonly);
-- Then, the numering module can use the column calculated_numrefonly to get the max with SELECT MAX(calculated_numrefonly) FROM llx_ticket
