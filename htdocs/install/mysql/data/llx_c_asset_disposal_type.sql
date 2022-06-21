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
--
--

--
-- Do not include comments at end of line, this file is parsed during install and string '--' are removed.
--

INSERT INTO llx_c_asset_disposal_type (rowid, entity, code, label, active) VALUES (1, 1, 'C', 'Sale', 1);
INSERT INTO llx_c_asset_disposal_type (rowid, entity, code, label, active) VALUES (2, 1, 'HS', 'Putting out of service', 1);
INSERT INTO llx_c_asset_disposal_type (rowid, entity, code, label, active) VALUES (3, 1, 'D', 'Destruction', 1);
