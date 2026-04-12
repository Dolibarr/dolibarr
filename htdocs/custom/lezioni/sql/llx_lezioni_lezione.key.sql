-- Copyright (C) 2024 SuperAdmin <wheelbiteasd@gmail.com>
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
ALTER TABLE llx_lezioni_lezione ADD INDEX idx_lezioni_lezione_rowid (rowid);
ALTER TABLE llx_lezioni_lezione ADD UNIQUE INDEX uk_lezioni_lezione_ref (ref);
ALTER TABLE llx_lezioni_lezione ADD INDEX idx_lezioni_lezione_fk_soc (fk_soc);
ALTER TABLE llx_lezioni_lezione ADD INDEX idx_lezioni_lezione_fk_project (fk_project);
ALTER TABLE llx_lezioni_lezione ADD INDEX idx_lezioni_lezione_status (status);
ALTER TABLE llx_lezioni_lezione ADD INDEX idx_lezioni_lezione_istruttore (istruttore);
ALTER TABLE llx_lezioni_lezione ADD INDEX idx_lezioni_lezione_allievo (allievo);
ALTER TABLE llx_lezioni_lezione ADD INDEX idx_lezioni_lezione_bank_transaction (bank_transaction);
ALTER TABLE llx_lezioni_lezione ADD INDEX idx_lezioni_lezione_fk_pacchetto (fk_pacchetto);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_lezioni_lezione ADD UNIQUE INDEX uk_lezioni_lezione_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_lezioni_lezione ADD CONSTRAINT llx_lezioni_lezione_fk_field FOREIGN KEY (fk_field) REFERENCES llx_lezioni_myotherobject(rowid);

