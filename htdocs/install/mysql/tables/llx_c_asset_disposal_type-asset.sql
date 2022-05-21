-- ========================================================================
-- Copyright (C) 2022      OpenDSI              <support@open-dsi.fr>
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
-- ========================================================================
--
-- Table to store disposal type for a fixed asset

CREATE TABLE llx_c_asset_disposal_type
(
    rowid    integer            AUTO_INCREMENT PRIMARY KEY,
    entity 	 integer            NOT NULL DEFAULT 1,
    code     varchar(16)        NOT NULL,
    label    varchar(50)        NOT NULL,
    active   integer            DEFAULT 1 NOT NULL
)ENGINE=innodb;
