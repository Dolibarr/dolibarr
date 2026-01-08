-- ========================================================================
-- Copyright (C) 2022      OpenDSI              <support@open-dsi.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
-- ========================================================================
--
-- Table for fixed asset model
--
-- Data example:
-- INSERT INTO llx_asset_model (entity, ref, label, asset_type, note_public, note_private, date_creation, tms, fk_user_creat, fk_user_modif, import_key, status) VALUES
-- (1, 'LAPTOP', 'Laptop', 1, NULL, NULL, '2022-01-18 14:27:09', '2022-01-24 09:31:49', 1, 1, NULL, 1);

CREATE TABLE llx_asset_model(
    rowid					integer			AUTO_INCREMENT PRIMARY KEY NOT NULL,
    entity					integer			DEFAULT 1 NOT NULL,  -- multi company id
    ref						varchar(128)	NOT NULL,
    ref_ext                 varchar(255),
    label					varchar(255)	NOT NULL,
    asset_type				smallint		NOT NULL,
    fk_pays                 integer         DEFAULT 0,
    note_public				text,
    note_private			text,
    date_creation			datetime		NOT NULL,
    tms                     timestamp       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_creat			integer			NOT NULL,
    fk_user_modif			integer,
    import_key				varchar(14),
    status					smallint		NOT NULL
) ENGINE=innodb;
