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
ALTER TABLE llx_handson_saison ADD INDEX idx_handson_saison_rowid (rowid);
ALTER TABLE llx_handson_saison ADD INDEX idx_handson_saison_ref (ref);
ALTER TABLE llx_handson_saison ADD INDEX idx_handson_saison_season_start (season_start);
ALTER TABLE llx_handson_saison ADD INDEX idx_handson_saison_season_end (season_end);
ALTER TABLE llx_handson_saison ADD INDEX idx_handson_saison_register_start (register_start);
ALTER TABLE llx_handson_saison ADD INDEX idx_handson_saison_register_end (register_end);
ALTER TABLE llx_handson_saison ADD INDEX idx_handson_saison_edit_end (edit_end);
ALTER TABLE llx_handson_saison ADD INDEX idx_handson_saison_assignment_start (assignment_start);
ALTER TABLE llx_handson_saison ADD INDEX idx_handson_saison_players_age_min (players_age_min);
ALTER TABLE llx_handson_saison ADD INDEX idx_handson_saison_players_age_max (players_age_max);
ALTER TABLE llx_handson_saison ADD INDEX idx_handson_saison_players_count_min (players_count_min);
ALTER TABLE llx_handson_saison ADD INDEX idx_handson_saison_players_count_max (players_count_max);
ALTER TABLE llx_handson_saison ADD CONSTRAINT llx_handson_saison_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_handson_saison ADD UNIQUE INDEX uk_handson_saison_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_handson_saison ADD CONSTRAINT llx_handson_saison_fk_field FOREIGN KEY (fk_field) REFERENCES llx_handson_myotherobject(rowid);

