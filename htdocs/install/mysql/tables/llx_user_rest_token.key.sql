-- ============================================================================
-- Copyright (C) 2025	Marc de Lima Lucio	<marc-dll@user.noreply.github.com>
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

ALTER TABLE llx_user_rest_token ADD UNIQUE INDEX uk_user_rest_token_token (ftoken);
ALTER TABLE llx_user_rest_token ADD CONSTRAINT fk_user_rest_token_rights_fk_user FOREIGN KEY (fk_user) REFERENCES llx_user (rowid);
