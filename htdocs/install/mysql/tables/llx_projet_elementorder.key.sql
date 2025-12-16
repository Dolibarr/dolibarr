-- ============================================================================
-- Copyright (C) 2025
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
-- ============================================================================

ALTER TABLE llx_projet_elementorder ADD UNIQUE INDEX uk_projet_elementorder (entity, fk_projet, elementtype, fk_element);

ALTER TABLE llx_projet_elementorder ADD INDEX idx_projet_elementorder_rank (entity, fk_projet, elementtype, rang);

ALTER TABLE llx_projet_elementorder ADD CONSTRAINT fk_projet_elementorder_fk_projet FOREIGN KEY (fk_projet) REFERENCES llx_projet (rowid);

