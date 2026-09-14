-- Copyright (C) 2026 Frédéric France      <frederic.france@free.fr>
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


-- Lookup "which objects of this type were deleted since date X".
ALTER TABLE llx_deletion_log ADD INDEX idx_deletion_log_element (element_type, entity, date_deletion);

-- Used by the retention purge.
ALTER TABLE llx_deletion_log ADD INDEX idx_deletion_log_date_deletion (date_deletion);
