-- ============================================================================
-- Copyright (C) 2017	Laurent Destailleur	<eldy@users.sourceforge.net>
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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ============================================================================


ALTER TABLE llx_ecm_files ADD UNIQUE INDEX uk_ecm_files (filepath, filename, entity);
ALTER TABLE llx_ecm_files ADD INDEX idx_ecm_files_label (label);

--ALTER TABLE llx_ecm_files ADD UNIQUE INDEX uk_ecm_files_fullpath(fullpath); Disabled, mysql limits size of index


