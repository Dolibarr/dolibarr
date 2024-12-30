-- ===================================================================
-- Copyright (C) 2024 Laurent Destailleur <eldy@users.sourceforge.net>
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
-- ===================================================================

ALTER TABLE llx_blockedlog ADD INDEX signature (signature);
ALTER TABLE llx_blockedlog ADD INDEX fk_object_element (fk_object,element);
ALTER TABLE llx_blockedlog ADD INDEX entity (entity);
ALTER TABLE llx_blockedlog ADD INDEX fk_user (fk_user);
ALTER TABLE llx_blockedlog ADD INDEX entity_action_certified (entity,action,certified);

ALTER TABLE llx_blockedlog ADD INDEX entity_rowid (entity, rowid);	-- for the "SELECT rowid, signature FROM llx_blockedlog FORCE INDEX entity_rowid WHERE entity = x AND rowid < z ORDER BY rowid DESC"
