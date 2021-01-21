-- ============================================================================
-- Copyright (C) 2011 Regis Houssin  <regis.houssin@inodbox.com>
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
-- ===========================================================================

ALTER TABLE llx_usergroup_user ADD UNIQUE INDEX uk_usergroup_user (entity,fk_user,fk_usergroup);

ALTER TABLE llx_usergroup_user ADD CONSTRAINT fk_usergroup_user_fk_user      FOREIGN KEY (fk_user)         REFERENCES llx_user (rowid);
ALTER TABLE llx_usergroup_user ADD CONSTRAINT fk_usergroup_user_fk_usergroup FOREIGN KEY (fk_usergroup)    REFERENCES llx_usergroup (rowid);
