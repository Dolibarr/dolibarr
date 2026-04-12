-- Copyright (C) 2024 Luca Fumagalli <luca.fuma@aol.it>
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
ALTER TABLE llx_lezioni_pacchetto ADD INDEX idx_lezioni_pacchetto_rowid (rowid);
ALTER TABLE llx_lezioni_pacchetto ADD UNIQUE INDEX uk_lezioni_pacchetto_ref (ref);
ALTER TABLE llx_lezioni_pacchetto ADD INDEX idx_lezioni_pacchetto_fk_soc (fk_soc);
ALTER TABLE llx_lezioni_pacchetto ADD INDEX idx_lezioni_pacchetto_fk_project (fk_project);
ALTER TABLE llx_lezioni_pacchetto ADD INDEX idx_lezioni_pacchetto_status (status);
ALTER TABLE llx_lezioni_pacchetto ADD INDEX idx_lezioni_pacchetto_allievo (allievo);
ALTER TABLE llx_lezioni_pacchetto ADD INDEX idx_lezioni_pacchetto_stato_pacchetto (stato_pacchetto);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_lezioni_pacchetto ADD UNIQUE INDEX uk_lezioni_pacchetto_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_lezioni_pacchetto ADD CONSTRAINT llx_lezioni_pacchetto_fk_field FOREIGN KEY (fk_field) REFERENCES llx_lezioni_myotherobject(rowid);

